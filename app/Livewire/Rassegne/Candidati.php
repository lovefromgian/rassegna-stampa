<?php

namespace App\Livewire\Rassegne;

use App\Enums\StatoUscita;
use App\Livewire\Concerns\NotificaUtente;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Services\Audit;
use App\Services\GestioneCattura;
use App\Services\ScansioneRassegna;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Schermata candidati (mockup 06): qui si decide solo dentro/fuori, con selezione multipla.
 * La rilevanza NON si assegna qui (§5): l'operatore non ha ancora letto l'articolo.
 * Corrispondenza debole segnalata, mai esclusa d'ufficio. Scansione manuale a richiesta.
 */
class Candidati extends Component
{
    use NotificaUtente;

    public Rassegna $rassegna;

    /** @var array<int, int> */
    public array $selezionati = [];

    public function mount(Rassegna $rassegna): void
    {
        Gate::authorize('view', $rassegna);
        $this->rassegna = $rassegna;
    }

    /** @return Collection<int, Uscita> */
    private function candidati()
    {
        return $this->rassegna->uscite()
            ->where('stato', StatoUscita::Candidato)
            ->with('testata')
            ->orderByDesc('punteggio_corrispondenza')
            ->orderByDesc('data_pubblicazione')
            ->get();
    }

    public function selezionaTutti(bool $tutti): void
    {
        $this->selezionati = $tutti ? $this->candidati()->pluck('id')->all() : [];
    }

    public function confermaSelezionati(GestioneCattura $cattura): void
    {
        $uscite = $this->usciteSelezionate();

        foreach ($uscite as $uscita) {
            Gate::authorize('update', $uscita);
            $uscita->update(['stato' => StatoUscita::Confermato]);
            Audit::registra('conferma_candidato', $uscita);
            // Online → parte la cattura; media manuali restano confermati in attesa di file.
            $cattura->avvia($uscita);
        }

        $n = $uscite->count();
        $this->selezionati = [];
        $this->notifica("{$n} ".($n === 1 ? 'candidato confermato' : 'candidati confermati').'.');
    }

    public function scartaSelezionati(): void
    {
        $uscite = $this->usciteSelezionate();

        foreach ($uscite as $uscita) {
            Gate::authorize('update', $uscita);
            $uscita->update(['stato' => StatoUscita::Scartato]);
            Audit::registra('scarto_uscita', $uscita);
        }

        $n = $uscite->count();
        $this->selezionati = [];
        $this->notifica("{$n} ".($n === 1 ? 'candidato scartato' : 'candidati scartati').'.');
    }

    public function scansionaOra(ScansioneRassegna $scansione): void
    {
        Gate::authorize('update', $this->rassegna);

        $nuovi = $scansione->scansiona($this->rassegna);
        $this->notifica($nuovi > 0
            ? "Scansione completata: {$nuovi} nuovi candidati."
            : 'Scansione completata: nessun nuovo candidato trovato nel periodo.');
    }

    /** @return Collection<int, Uscita> */
    private function usciteSelezionate()
    {
        return $this->rassegna->uscite()
            ->where('stato', StatoUscita::Candidato)
            ->whereKey($this->selezionati)
            ->get();
    }

    public function render(): View
    {
        $candidati = $this->candidati();

        // Sospetto duplicato (§3): stessa testata e data, titolo molto simile a un altro.
        $sospetti = [];
        foreach ($candidati as $c) {
            foreach ($candidati as $altro) {
                if ($altro->id === $c->id || $altro->testata_id !== $c->testata_id) {
                    continue;
                }
                if (! $altro->data_pubblicazione->isSameDay($c->data_pubblicazione)) {
                    continue;
                }
                similar_text(mb_strtolower($c->titolo), mb_strtolower($altro->titolo), $percentuale);
                if ($percentuale >= 70) {
                    $sospetti[$c->id] = $altro->testata->nome;
                    break;
                }
            }
        }

        return view('livewire.rassegne.candidati', [
            'candidati' => $candidati,
            'sospetti' => $sospetti,
            'ultimaScansione' => $candidati->max('data_rilevamento'),
        ]);
    }
}
