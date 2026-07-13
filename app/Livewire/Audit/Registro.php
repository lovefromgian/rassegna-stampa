<?php

namespace App\Livewire\Audit;

use App\Models\DocumentoGenerato;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Consultazione del log di audit (mockup 10), globale o per rassegna. IMMUTABILE: solo
 * lettura, nessuna azione di modifica/cancellazione (regole-business.md §11).
 */
class Registro extends Component
{
    use WithPagination;

    public ?Rassegna $rassegna = null;

    public string $azione = '';

    public ?int $utenteId = null;

    public function mount(?Rassegna $rassegna = null): void
    {
        Gate::authorize('viewAny', LogAzione::class);
        $this->rassegna = $rassegna?->exists ? $rassegna : null;
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = LogAzione::query()->with('user')->latest();

        if ($this->rassegna) {
            $usciteIds = $this->rassegna->uscite()->withTrashed()->pluck('id')->all();
            $docIds = $this->rassegna->documentiGenerati()->pluck('id')->all();

            $query->where(function ($q) use ($usciteIds, $docIds) {
                $q->where(fn ($q) => $q->where('entita_tipo', Rassegna::class)->where('entita_id', $this->rassegna->id))
                    ->orWhere(fn ($q) => $q->where('entita_tipo', Uscita::class)->whereIn('entita_id', $usciteIds))
                    ->orWhere(fn ($q) => $q->where('entita_tipo', DocumentoGenerato::class)->whereIn('entita_id', $docIds));
            });
        }

        if ($this->azione !== '') {
            $query->where('azione', $this->azione);
        }
        if ($this->utenteId) {
            $query->where('user_id', $this->utenteId);
        }

        return view('livewire.audit.registro', [
            'voci' => $query->paginate(30),
            'azioniDisponibili' => LogAzione::query()->distinct()->orderBy('azione')->pluck('azione'),
            'utenti' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
