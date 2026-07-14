<?php

namespace App\Livewire\Rassegne;

use App\Enums\Rilevanza;
use App\Enums\StatoCattura;
use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Livewire\Concerns\NotificaUtente;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Services\Audit;
use App\Services\GestioneCattura;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Revisione delle uscite catturate (mockup 07): l'operatore verifica la cattura, corregge
 * il tipo di media, assegna la rilevanza, annota, e approva o scarta. La rilevanza si
 * assegna QUI, non in conferma (regole-business.md §5). Una uscita alla volta.
 */
class Revisione extends Component
{
    use NotificaUtente, WithFileUploads;

    public Rassegna $rassegna;

    public ?int $correnteId = null;

    #[Validate('required')]
    public string $tipo_media = '';

    #[Validate('required')]
    public string $rilevanza = '';

    #[Validate('nullable|string')]
    public string $note = '';

    /** File di sostituzione (screenshot/ritaglio) caricato a mano durante la revisione. */
    public $fileSostitutivo = null;

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

    /**
     * Sostituisce a mano il materiale dell'uscita in revisione (screenshot rovinato dal
     * banner cookie o paywall, oppure ritaglio cartaceo). Resta sulla stessa uscita così
     * l'operatore vede il nuovo file e poi approva — senza cambiare schermata.
     */
    public function sostituisciFile(): void
    {
        $uscita = $this->corrente();
        abort_if(! $uscita, 404);
        Gate::authorize('update', $uscita);

        $this->validate([
            'fileSostitutivo' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        if ($uscita->file_caricato_path) {
            Storage::disk(config('capture.disk'))->delete($uscita->file_caricato_path);
        }

        $uscita->update([
            'file_caricato_path' => $this->fileSostitutivo->store('ritagli', config('capture.disk')),
            'stato_cattura' => null,
            'errore_cattura' => null,
        ]);

        $this->reset('fileSostitutivo');
        $this->notifica('File sostituito.');
    }

    public function render(): View
    {
        $corrente = $this->corrente();

        $totale = $this->rassegna->uscite()
            ->whereIn('stato', [StatoUscita::Catturato, StatoUscita::Approvato])
            ->count();
        $rimanenti = $this->daRevisionare()->count();

        // Uscite confermate ancora in cattura: quando il worker finisce diventano
        // "catturato" e compaiono qui. Serve a distinguere "attendi" da "finito".
        $inCattura = $this->rassegna->uscite()
            ->where('stato', StatoUscita::Confermato)
            ->whereIn('stato_cattura', [StatoCattura::InAttesa, StatoCattura::InCorso])
            ->count();

        return view('livewire.rassegne.revisione', [
            'uscita' => $corrente,
            'indice' => $totale - $rimanenti + 1,
            'totale' => $totale,
            'rimanenti' => $rimanenti,
            'inCattura' => $inCattura,
            'tipiMedia' => TipoMedia::cases(),
            'rilevanze' => Rilevanza::cases(),
        ]);
    }
}
