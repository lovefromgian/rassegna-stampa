<?php

namespace App\Livewire\Rassegne;

use App\Enums\StatoRassegna;
use App\Livewire\Concerns\NotificaUtente;
use App\Models\Rassegna;
use App\Services\Audit;
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

        return view('livewire.rassegne.scheda', [
            'puoModificare' => Gate::allows('update', $this->rassegna),
            'puoEliminare' => Gate::allows('delete', $this->rassegna),
            'puoRiaprire' => Gate::allows('riapri', $this->rassegna),
        ]);
    }
}
