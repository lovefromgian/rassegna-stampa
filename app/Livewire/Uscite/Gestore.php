<?php

namespace App\Livewire\Uscite;

use App\Enums\StatoCattura;
use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Livewire\Concerns\NotificaUtente;
use App\Models\Rassegna;
use App\Models\Testata;
use App\Models\Uscita;
use App\Services\GestioneCattura;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Gestione delle uscite di una rassegna (M2): aggiunta manuale, avvio/ricattura,
 * sostituzione manuale del file, scarto. La revisione e la rilevanza sono M3.
 * Autorizzazione lato server via Policy Uscita.
 */
class Gestore extends Component
{
    use NotificaUtente, WithFileUploads;

    public Rassegna $rassegna;

    /** Filtro per stato (nell'URL come ?stato=…), es. arrivando dal quadrato "Scartate". */
    #[Url(as: 'stato')]
    public string $filtroStato = '';

    // --- Form "nuova uscita" ---
    public bool $mostraForm = false;

    public string $tipo_media = 'online';

    public string $titolo = '';

    public string $testata_nome = '';

    public ?string $data_pubblicazione = null;

    public string $url = '';

    public string $pagina_giornale = '';

    public $fileRitaglio = null;

    // Upload per la sostituzione manuale del file di un'uscita esistente.
    public ?int $uscitaFileId = null;

    public $fileSostitutivo = null;

    public function mount(Rassegna $rassegna): void
    {
        Gate::authorize('view', $rassegna);
        $this->rassegna = $rassegna;
    }

    public function nuovaUscita(): void
    {
        Gate::authorize('create', Uscita::class);
        $this->reset(['tipo_media', 'titolo', 'testata_nome', 'data_pubblicazione', 'url', 'pagina_giornale', 'fileRitaglio']);
        $this->tipo_media = 'online';
        $this->mostraForm = true;
    }

    public function annulla(): void
    {
        $this->mostraForm = false;
        $this->resetValidation();
    }

    public function salvaUscita(GestioneCattura $cattura): void
    {
        Gate::authorize('create', Uscita::class);

        $online = $this->tipo_media === TipoMedia::Online->value;

        $this->validate([
            'tipo_media' => ['required', Rule::enum(TipoMedia::class)],
            'titolo' => ['required', 'string', 'max:255'],
            'testata_nome' => ['required', 'string', 'max:255'],
            'data_pubblicazione' => ['required', 'date'],
            'url' => [
                $online ? 'required' : 'nullable',
                'nullable', 'url', 'max:2048',
                // Deduplica: una URL già presente nella stessa rassegna non si ripropone.
                Rule::unique('uscite', 'url')->where('rassegna_id', $this->rassegna->id),
            ],
            'pagina_giornale' => ['nullable', 'string', 'max:100'],
            'fileRitaglio' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ], attributes: [
            'testata_nome' => 'testata',
            'data_pubblicazione' => 'data di pubblicazione',
            'url' => 'URL',
        ]);

        $testata = Testata::firstOrCreate(
            ['nome' => trim($this->testata_nome)],
            ['tipo_prevalente' => $this->tipo_media],
        );

        $filePath = null;
        if ($this->fileRitaglio) {
            $filePath = $this->fileRitaglio->store('ritagli', config('capture.disk'));
        }

        // Media manuale con file, oppure online: parte da "confermato" (l'operatore
        // aggiunge un'uscita che sa essere dentro). Con materiale caricato → "catturato".
        $haMateriale = $filePath !== null;

        $uscita = Uscita::create([
            'rassegna_id' => $this->rassegna->id,
            'testata_id' => $testata->id,
            'titolo' => $this->titolo,
            'data_pubblicazione' => $this->data_pubblicazione,
            'url' => $online ? $this->url : null,
            'tipo_media' => $this->tipo_media,
            'stato' => $haMateriale ? StatoUscita::Catturato : StatoUscita::Confermato,
            'pagina_giornale' => $this->pagina_giornale ?: null,
            'file_caricato_path' => $filePath,
            'data_rilevamento' => now(),
        ]);

        // Online senza materiale caricato → accoda subito la cattura web.
        if ($online && ! $haMateriale) {
            $cattura->avvia($uscita);
        }

        $this->mostraForm = false;
        $this->reset(['titolo', 'testata_nome', 'data_pubblicazione', 'url', 'pagina_giornale', 'fileRitaglio']);
        $this->notifica('Uscita aggiunta.');
    }

    public function avviaCattura(int $uscitaId, GestioneCattura $cattura): void
    {
        $uscita = $this->uscitaDellaRassegna($uscitaId);
        Gate::authorize('update', $uscita);

        if ($cattura->avvia($uscita)) {
            $this->notifica('Cattura accodata.');
        }
    }

    public function salvaFileSostitutivo(): void
    {
        $uscita = $this->uscitaDellaRassegna($this->uscitaFileId);
        Gate::authorize('update', $uscita);

        $this->validate([
            'fileSostitutivo' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        // Sostituzione manuale del file: rimpiazza l'eventuale precedente sul disco Laravel.
        if ($uscita->file_caricato_path) {
            Storage::disk(config('capture.disk'))->delete($uscita->file_caricato_path);
        }

        $uscita->update([
            'file_caricato_path' => $this->fileSostitutivo->store('ritagli', config('capture.disk')),
            'stato' => StatoUscita::Catturato,
            'stato_cattura' => null,
            'errore_cattura' => null,
        ]);

        $this->reset(['uscitaFileId', 'fileSostitutivo']);
        $this->notifica('File sostituito.');
    }

    public function scarta(int $uscitaId): void
    {
        $uscita = $this->uscitaDellaRassegna($uscitaId);
        Gate::authorize('update', $uscita);

        $uscita->update(['stato' => StatoUscita::Scartato]);
        $this->notifica('Uscita scartata (resta archiviata e recuperabile).');
    }

    private function uscitaDellaRassegna(?int $id): Uscita
    {
        return $this->rassegna->uscite()->findOrFail($id);
    }

    public function render(): View
    {
        $uscite = $this->rassegna->uscite()
            ->with('testata')
            ->when($this->filtroStato !== '', fn ($q) => $q->where('stato', $this->filtroStato))
            ->orderByDesc('data_pubblicazione')
            ->get();

        return view('livewire.uscite.gestore', [
            'uscite' => $uscite,
            'tipiMedia' => TipoMedia::cases(),
            'statiUscita' => StatoUscita::cases(),
            'isOnline' => $this->tipo_media === TipoMedia::Online->value,
            'puoAggiungere' => Gate::allows('create', Uscita::class),
            'statiInCattura' => [StatoCattura::InAttesa, StatoCattura::InCorso],
        ]);
    }
}
