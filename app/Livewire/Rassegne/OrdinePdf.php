<?php

namespace App\Livewire\Rassegne;

use App\Enums\StatoUscita;
use App\Jobs\GeneraPdf;
use App\Models\DocumentoGenerato;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Services\BlocchiGenerazione;
use App\Services\GeneratorePdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Ordine delle uscite nel PDF e generazione (mockup 08). Ordinamento proposto = rilevanza
 * poi data; l'operatore riordina a mano (posizione_pdf, che prevale, §6). Generazione con
 * blocchi §7 e versionamento.
 */
class OrdinePdf extends Component
{
    public Rassegna $rassegna;

    public function mount(Rassegna $rassegna): void
    {
        Gate::authorize('view', $rassegna);
        $this->rassegna = $rassegna;
    }

    public function spostaSu(int $uscitaId): void
    {
        $this->sposta($uscitaId, -1);
    }

    public function spostaGiu(int $uscitaId): void
    {
        $this->sposta($uscitaId, 1);
    }

    private function sposta(int $uscitaId, int $direzione): void
    {
        Gate::authorize('update', $this->rassegna);

        $ids = app(GeneratorePdf::class)->usciteOrdinate($this->rassegna)->pluck('id')->all();
        $pos = array_search($uscitaId, $ids, true);
        if ($pos === false) {
            return;
        }
        $nuova = $pos + $direzione;
        if ($nuova < 0 || $nuova >= count($ids)) {
            return;
        }

        [$ids[$pos], $ids[$nuova]] = [$ids[$nuova], $ids[$pos]];

        // Persisto l'ordine manuale: posizione_pdf sequenziale, prevale sulla proposta.
        foreach ($ids as $i => $id) {
            Uscita::whereKey($id)->update(['posizione_pdf' => $i + 1]);
        }
    }

    public function genera(BlocchiGenerazione $blocchi): void
    {
        Gate::authorize('create', DocumentoGenerato::class);

        $motivi = $blocchi->motivi($this->rassegna);
        if ($motivi !== []) {
            session()->flash('error', 'Impossibile generare il PDF: '.implode(' ', $motivi));

            return;
        }

        GeneraPdf::dispatch($this->rassegna, auth()->user());
        session()->flash('success', 'Generazione avviata: la nuova versione comparirà tra pochi istanti.');
    }

    public function render(): View
    {
        $uscite = app(GeneratorePdf::class)->usciteOrdinate($this->rassegna);
        $motivi = app(BlocchiGenerazione::class)->motivi($this->rassegna);

        $documenti = $this->rassegna->documentiGenerati()
            ->with('generatoDa')
            ->orderByDesc('versione')
            ->get();

        return view('livewire.rassegne.ordine-pdf', [
            'uscite' => $uscite,
            'motivi' => $motivi,
            'puoGenerare' => $motivi === [],
            'documenti' => $documenti,
            'candidatiPendenti' => $this->rassegna->uscite()->where('stato', StatoUscita::Candidato)->count(),
            'prossimaVersione' => ((int) $this->rassegna->documentiGenerati()->max('versione')) + 1,
        ]);
    }
}
