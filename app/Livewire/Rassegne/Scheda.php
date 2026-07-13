<?php

namespace App\Livewire\Rassegne;

use App\Enums\StatoRassegna;
use App\Enums\StatoUscita;
use App\Livewire\Concerns\NotificaUtente;
use App\Models\Rassegna;
use App\Services\Audit;
use App\Services\BlocchiGenerazione;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Scheda extends Component
{
    use NotificaUtente;

    public Rassegna $rassegna;

    public function mount(Rassegna $rassegna): void
    {
        Gate::authorize('view', $rassegna);
        $this->rassegna = $rassegna;
    }

    /** Chiude la raccolta: in_raccolta → in_revisione. Ferma la scansione giornaliera. */
    public function chiudiRaccolta(): void
    {
        Gate::authorize('update', $this->rassegna);

        if ($this->rassegna->stato !== StatoRassegna::InRaccolta) {
            return;
        }

        $this->rassegna->update(['stato' => StatoRassegna::InRevisione]);
        Audit::registra('chiude_raccolta', $this->rassegna);
        $this->notifica('Raccolta chiusa: la rassegna è in revisione.');
    }

    /**
     * Chiude la rassegna: in_revisione → chiusa. Solo se è stato generato almeno un PDF
     * (regole-business.md §9: si chiude quando il PDF è generato e consegnato).
     */
    public function chiudiRassegna(): void
    {
        Gate::authorize('update', $this->rassegna);

        if ($this->rassegna->stato !== StatoRassegna::InRevisione) {
            return;
        }
        if ($this->rassegna->documentiGenerati()->count() === 0) {
            $this->notifica('Genera prima il PDF: la rassegna si chiude solo con una versione generata.', 'error');

            return;
        }

        $this->rassegna->update(['stato' => StatoRassegna::Chiusa]);
        Audit::registra('chiude_rassegna', $this->rassegna);
        $this->notifica('Rassegna chiusa.');
    }

    /**
     * Riapre una rassegna chiusa: solo il supervisore (RassegnaPolicy::riapri). Non cancella
     * il PDF già generato: si aggiungono uscite e si genera una nuova versione (§9).
     */
    public function riapri(): void
    {
        Gate::authorize('riapri', $this->rassegna);

        if (! in_array($this->rassegna->stato, [StatoRassegna::Chiusa, StatoRassegna::Riaperta], true)) {
            return;
        }

        $this->rassegna->update(['stato' => StatoRassegna::Riaperta]);
        Audit::registra('riapre_rassegna', $this->rassegna);
        $this->notifica('Rassegna riaperta: aggiungi le uscite tardive e genera una nuova versione.');
    }

    public function render(): View
    {
        $this->rassegna->load('cliente');

        // Conteggi per stato (fonte unica sul modello: usata anche dalla mappa fasi UX-04).
        $conteggi = $this->rassegna->conteggiPerStato();

        $candidati = $conteggi[StatoUscita::Candidato->value] ?? 0;
        $catturato = $conteggi[StatoUscita::Catturato->value] ?? 0;
        $confermato = $conteggi[StatoUscita::Confermato->value] ?? 0; // in cattura
        $approvate = $conteggi[StatoUscita::Approvato->value] ?? 0;
        $scartate = $conteggi[StatoUscita::Scartato->value] ?? 0;

        // Passo consigliato contestuale (UX-01): un solo primario, con conteggio.
        $daLavorare = $catturato + $confermato;
        $prossimo = match (true) {
            $this->rassegna->stato === StatoRassegna::Chiusa => 'chiusa',
            $candidati > 0 => 'conferma',
            $daLavorare > 0 => 'revisiona',
            default => 'pdf',
        };

        // Nota con il motivo reale di blocco del PDF (riusa BlocchiGenerazione).
        $motivi = app(BlocchiGenerazione::class)->motivi($this->rassegna);
        if ($this->rassegna->stato === StatoRassegna::Chiusa) {
            $nota = 'Rassegna chiusa. Per aggiungere uscite tardive, riaprila (supervisore) e genera una nuova versione.';
        } elseif ($confermato > 0 && $catturato === 0 && $candidati === 0) {
            $nota = "Cattura in corso: {$confermato} ".($confermato === 1 ? 'uscita' : 'uscite').'. Attendi il completamento, poi revisiona.';
        } else {
            $nota = $motivi !== [] ? implode(' ', $motivi) : 'Tutto pronto: nessun blocco alla generazione del PDF.';
        }

        return view('livewire.rassegne.scheda', [
            'puoModificare' => Gate::allows('update', $this->rassegna),
            'puoEliminare' => Gate::allows('delete', $this->rassegna),
            'puoRiaprire' => Gate::allows('riapri', $this->rassegna),
            'metriche' => [
                'candidati' => $candidati,
                'daRevisionare' => $catturato,
                'approvate' => $approvate,
                'scartate' => $scartate,
            ],
            'inCattura' => $confermato,
            'prossimo' => $prossimo,
            'nota' => $nota,
            // Fase evidenziata nello stepper della scheda = quella consigliata.
            'faseCorrente' => match ($prossimo) {
                'conferma' => 'candidati',
                'revisiona' => 'revisione',
                default => 'pdf',
            },
        ]);
    }
}
