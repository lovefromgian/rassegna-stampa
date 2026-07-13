<?php

namespace App\Services;

use App\Enums\StatoUscita;
use App\Models\DocumentoGenerato;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Genera il PDF impaginato della rassegna (regole-business.md §8): impianto grafico unico,
 * personalizzato solo con logo e colore d'accento del cliente. Versionato: non sovrascrive
 * mai, crea una nuova versione con snapshot delle uscite incluse.
 */
class GeneratorePdf
{
    public function __construct(private BlocchiGenerazione $blocchi) {}

    /**
     * Uscite approvate nell'ordine del PDF: l'ordine manuale (posizione_pdf) prevale;
     * in assenza, ordinamento proposto = rilevanza (principale→citazione) poi data (§6).
     *
     * @return Collection<int, Uscita>
     */
    public function usciteOrdinate(Rassegna $rassegna): Collection
    {
        return $rassegna->uscite()
            ->where('stato', StatoUscita::Approvato)
            ->with('testata')
            ->get()
            ->sortBy(fn (Uscita $u) => [
                $u->posizione_pdf ?? PHP_INT_MAX,
                $u->rilevanza?->peso() ?? 9,
                $u->data_pubblicazione->timestamp,
            ])
            ->values();
    }

    /**
     * Genera una nuova versione del PDF. Lancia se i blocchi §7 non sono soddisfatti.
     */
    public function genera(Rassegna $rassegna, User $autore): DocumentoGenerato
    {
        $motivi = $this->blocchi->motivi($rassegna);
        if ($motivi !== []) {
            throw new RuntimeException('Generazione bloccata: '.implode(' ', $motivi));
        }

        $rassegna->loadMissing('cliente');
        $uscite = $this->usciteOrdinate($rassegna);

        $disk = Storage::disk(config('capture.disk'));

        // Immagini incorporate come data URI: dompdf le rende senza dipendere dai percorsi.
        $vociUscite = $uscite->map(fn (Uscita $u) => [
            'uscita' => $u,
            'immagine' => $this->immagineDataUri($u),
        ]);

        $html = view('pdf.rassegna', [
            'rassegna' => $rassegna,
            'cliente' => $rassegna->cliente,
            'coloreAccento' => $rassegna->cliente->colore_accento ?: '#185fa5',
            'logoDataUri' => $rassegna->cliente->logo_path ? $this->fileDataUri($rassegna->cliente->logo_path) : null,
            'voci' => $vociUscite,
        ])->render();

        $contenuto = Pdf::loadHTML($html)->setPaper('a4')->output();

        $versione = ((int) $rassegna->documentiGenerati()->max('versione')) + 1;
        $path = 'rassegne/'.$rassegna->id.'/v'.$versione.'-'.Str::uuid().'.pdf';
        $disk->put($path, $contenuto);

        return $rassegna->documentiGenerati()->create([
            'versione' => $versione,
            'file_path' => $path,
            'generato_da' => $autore->id,
            'generato_il' => now(),
            'uscite_incluse' => $uscite->pluck('id')->all(),
        ]);
    }

    /** Screenshot (online) o file caricato (media manuali) come data URI, se presente. */
    private function immagineDataUri(Uscita $uscita): ?string
    {
        $path = $uscita->screenshot_path ?: $uscita->file_caricato_path;

        // Solo immagini incorporabili; un PDF caricato a mano non si annida in dompdf.
        if (! $path || Str::endsWith(Str::lower($path), '.pdf')) {
            return null;
        }

        return $this->fileDataUri($path);
    }

    private function fileDataUri(string $path): ?string
    {
        $disk = Storage::disk(config('capture.disk'));
        if (! $disk->exists($path)) {
            return null;
        }

        $mime = match (Str::lower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
    }
}
