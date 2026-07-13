<?php

namespace App\Livewire\Rassegne;

use App\Enums\Rilevanza;
use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Livewire\Concerns\NotificaUtente;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Services\Audit;
use App\Services\GestioneCattura;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Revisione delle uscite catturate (mockup 07): l'operatore verifica la cattura, corregge
 * il tipo di media, assegna la rilevanza, annota, e approva o scarta. La rilevanza si
 * assegna QUI, non in conferma (regole-business.md §5). Una uscita alla volta.
 */
class Revisione extends Component
{
    use NotificaUtente;

    public Rassegna $rassegna;

    public ?int $correnteId = null;

    #[Validate('required')]
    public string $tipo_media = '';

    #[Validate('required')]
    public string $rilevanza = '';

    #[Validate('nullable|string')]
    public string $note = '';

    public function mount(Rassegna $rassegna): void
    {
        Gate::authorize('view', $rassegna);
        $this->rassegna = $rassegna;
        $this->caricaProssima();
    }

    /** Uscite ancora da revisionare: quelle catturate. */
    private function daRevisionare()
    {
        return $this->rassegna->uscite()
            ->where('stato', StatoUscita::Catturato)
            ->orderBy('data_rilevamento');
    }

    private function caricaProssima(): void
    {
        $uscita = $this->daRevisionare()->first();
        $this->resetValidation();

        if (! $uscita) {
            $this->correnteId = null;

            return;
        }

        $this->correnteId = $uscita->id;
        $this->tipo_media = $uscita->tipo_media->value;
        $this->rilevanza = $uscita->rilevanza?->value ?? Rilevanza::Principale->value;
        $this->note = $uscita->note ?? '';
    }

    private function corrente(): ?Uscita
    {
        return $this->correnteId
            ? $this->rassegna->uscite()->whereKey($this->correnteId)->first()
            : null;
    }

    public function approva(): void
    {
        $uscita = $this->corrente();
        abort_if(! $uscita, 404);
        Gate::authorize('update', $uscita);

        $this->validate([
            'tipo_media' => ['required', Rule::enum(TipoMedia::class)],
            'rilevanza' => ['required', Rule::enum(Rilevanza::class)],
        ]);

        $uscita->update([
            'tipo_media' => $this->tipo_media,
            'rilevanza' => $this->rilevanza,
            'note' => $this->note ?: null,
            'stato' => StatoUscita::Approvato,
        ]);

        Audit::registra('approva_uscita', $uscita, ['rilevanza' => $this->rilevanza]);
        $this->notifica('Uscita approvata.');
        $this->caricaProssima();
    }

    public function scarta(): void
    {
        $uscita = $this->corrente();
        abort_if(! $uscita, 404);
        Gate::authorize('update', $uscita);

        $uscita->update([
            'note' => $this->note ?: null,
            'stato' => StatoUscita::Scartato,
        ]);

        Audit::registra('scarto_uscita', $uscita);
        $this->notifica('Uscita scartata (resta archiviata e recuperabile).');
        $this->caricaProssima();
    }

    public function ricattura(GestioneCattura $cattura): void
    {
        $uscita = $this->corrente();
        abort_if(! $uscita, 404);
        Gate::authorize('update', $uscita);

        if ($cattura->avvia($uscita)) {
            $this->notifica('Ricattura accodata.');
            $this->caricaProssima();
        }
    }

    public function render(): View
    {
        $corrente = $this->corrente();

        $totale = $this->rassegna->uscite()
            ->whereIn('stato', [StatoUscita::Catturato, StatoUscita::Approvato])
            ->count();
        $rimanenti = $this->daRevisionare()->count();

        return view('livewire.rassegne.revisione', [
            'uscita' => $corrente,
            'indice' => $totale - $rimanenti + 1,
            'totale' => $totale,
            'rimanenti' => $rimanenti,
            'tipiMedia' => TipoMedia::cases(),
            'rilevanze' => Rilevanza::cases(),
        ]);
    }
}
