<?php

namespace App\Livewire\Rassegne;

use App\Jobs\ScansionaRassegna;
use App\Models\Cliente;
use App\Models\Rassegna;
use App\Services\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Creazione/modifica rassegna. Entrambi i ruoli (operatore e supervisore) possono
 * crearla e modificarla; l'eliminazione e la riapertura restano al supervisore.
 * Autorizzazione applicata lato server (Gate::authorize).
 */
class Modifica extends Component
{
    public ?Rassegna $rassegna = null;

    #[Validate('required|integer|exists:clienti,id')]
    public ?int $cliente_id = null;

    #[Validate('required|string|max:255')]
    public string $titolo = '';

    #[Validate('nullable|string|max:255')]
    public string $comunicato_titolo = '';

    #[Validate('nullable|string|max:255')]
    public string $comunicato_sottotitolo = '';

    #[Validate('nullable|date')]
    public ?string $comunicato_data = null;

    #[Validate('nullable|string')]
    public string $comunicato_testo = '';

    /** Testo grezzo: un termine per riga. */
    #[Validate('required|string')]
    public string $parole_chiave = '';

    #[Validate('nullable|string')]
    public string $parole_escluse = '';

    #[Validate('required|date')]
    public ?string $monitoraggio_inizio = null;

    #[Validate('required|date|after_or_equal:monitoraggio_inizio')]
    public ?string $monitoraggio_fine = null;

    /** Giorni di durata predefinita del monitoraggio dal comunicato (regole §periodo). */
    public int $durataDefaultGiorni = 14;

    public function mount(?Rassegna $rassegna = null): void
    {
        if ($rassegna && $rassegna->exists) {
            Gate::authorize('update', $rassegna);
            $this->rassegna = $rassegna;
            $this->cliente_id = $rassegna->cliente_id;
            $this->titolo = $rassegna->titolo;
            $this->comunicato_titolo = $rassegna->comunicato_titolo ?? '';
            $this->comunicato_sottotitolo = $rassegna->comunicato_sottotitolo ?? '';
            $this->comunicato_data = $rassegna->comunicato_data?->toDateString();
            $this->comunicato_testo = $rassegna->comunicato_testo ?? '';
            $this->parole_chiave = implode("\n", $rassegna->parole_chiave ?? []);
            $this->parole_escluse = implode("\n", $rassegna->parole_escluse ?? []);
            $this->monitoraggio_inizio = $rassegna->monitoraggio_inizio?->toDateString();
            $this->monitoraggio_fine = $rassegna->monitoraggio_fine?->toDateString();
        } else {
            Gate::authorize('create', Rassegna::class);
            // Preselezione del cliente quando si arriva dalla sua scheda.
            $this->cliente_id = request()->integer('cliente') ?: null;
        }
    }

    /**
     * Precompila il periodo dal comunicato: inizio = data comunicato, fine = +14 giorni.
     * Solo se l'utente non ha già impostato il periodo a mano (nessun caso speciale nel
     * codice: la regola vale per la rassegna con comunicato, docs/modello-dati.md).
     */
    public function updatedComunicatoData(?string $valore): void
    {
        if (! $valore) {
            return;
        }
        if ($this->monitoraggio_inizio === null || $this->monitoraggio_inizio === '') {
            $this->monitoraggio_inizio = $valore;
        }
        if ($this->monitoraggio_fine === null || $this->monitoraggio_fine === '') {
            $this->monitoraggio_fine = Carbon::parse($valore)
                ->addDays($this->durataDefaultGiorni)->toDateString();
        }
    }

    public function salva(): void
    {
        $this->rassegna
            ? Gate::authorize('update', $this->rassegna)
            : Gate::authorize('create', Rassegna::class);

        $this->validate();

        $payload = [
            'cliente_id' => $this->cliente_id,
            'titolo' => $this->titolo,
            'comunicato_titolo' => $this->comunicato_titolo ?: null,
            'comunicato_sottotitolo' => $this->comunicato_sottotitolo ?: null,
            'comunicato_data' => $this->comunicato_data ?: null,
            'comunicato_testo' => $this->comunicato_testo ?: null,
            'parole_chiave' => $this->righeInArray($this->parole_chiave),
            'parole_escluse' => $this->righeInArray($this->parole_escluse),
            'monitoraggio_inizio' => $this->monitoraggio_inizio,
            'monitoraggio_fine' => $this->monitoraggio_fine,
        ];

        if ($this->rassegna) {
            $this->rassegna->update($payload);
            Audit::registra('modifica_rassegna', $this->rassegna);
            session()->flash('success', 'Rassegna aggiornata.');
            $this->redirectRoute('rassegne.show', $this->rassegna, navigate: true);

            return;
        }

        $this->rassegna = Rassegna::create($payload);
        Audit::registra('crea_rassegna', $this->rassegna);

        // Alla creazione parte subito la ricerca automatica sul web (in coda: il
        // salvataggio resta istantaneo). L'operatore atterra sui candidati, che si
        // popolano man mano che il worker trova gli articoli.
        ScansionaRassegna::dispatch($this->rassegna);

        session()->flash('success', 'Rassegna creata. Sto cercando gli articoli sul web…');
        $this->redirectRoute('rassegne.candidati', $this->rassegna, navigate: true);
    }

    /**
     * @return list<string>
     */
    private function righeInArray(string $testo): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $testo))
            ->map(fn ($r) => trim($r))
            ->filter()
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.rassegne.modifica', [
            'clienti' => Cliente::orderBy('nome')->get(['id', 'nome']),
        ]);
    }
}
