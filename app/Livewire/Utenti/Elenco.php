<?php

namespace App\Livewire\Utenti;

use App\Livewire\Concerns\NotificaUtente;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Elenco degli utenti dell'agenzia. Solo supervisore: crea, modifica, attiva/disattiva.
 * Nessuna cancellazione (il log di audit li referenzia): si disattivano.
 */
class Elenco extends Component
{
    use NotificaUtente;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function cambiaAttivazione(int $userId): void
    {
        $utente = User::findOrFail($userId);
        Gate::authorize('attivazione', $utente);

        $utente->update(['attivo' => ! $utente->attivo]);
        Audit::registra($utente->attivo ? 'attiva_utente' : 'disattiva_utente', $utente);
        $this->notifica($utente->attivo ? 'Utente attivato.' : 'Utente disattivato (accesso revocato).');
    }

    public function render(): View
    {
        return view('livewire.utenti.elenco', [
            'utenti' => User::orderBy('name')->get(),
        ]);
    }
}
