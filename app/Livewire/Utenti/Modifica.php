<?php

namespace App\Livewire\Utenti;

use App\Enums\RuoloUtente;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Creazione/modifica di un utente. Solo supervisore (UserPolicy). In creazione la password
 * è obbligatoria; in modifica si cambia solo se compilata.
 */
class Modifica extends Component
{
    public ?User $utente = null;

    public string $name = '';

    public string $email = '';

    public string $ruolo = 'operatore';

    public string $password = '';

    public function mount(?User $utente = null): void
    {
        if ($utente && $utente->exists) {
            Gate::authorize('update', $utente);
            $this->utente = $utente;
            $this->name = $utente->name;
            $this->email = $utente->email;
            $this->ruolo = $utente->ruolo->value;
        } else {
            Gate::authorize('create', User::class);
        }
    }

    public function salva(): void
    {
        $this->utente
            ? Gate::authorize('update', $this->utente)
            : Gate::authorize('create', User::class);

        $dati = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->utente?->id)],
            'ruolo' => ['required', Rule::enum(RuoloUtente::class)],
            'password' => [$this->utente ? 'nullable' : 'required', 'string', 'min:8'],
        ]);

        $payload = [
            'name' => $this->name,
            'email' => $this->email,
            'ruolo' => $this->ruolo,
        ];
        if ($this->password !== '') {
            $payload['password'] = $this->password; // il cast 'hashed' del modello lo cifra
        }

        if ($this->utente) {
            $this->utente->update($payload);
            Audit::registra('modifica_utente', $this->utente);
            $messaggio = 'Utente aggiornato.';
        } else {
            $payload['attivo'] = true;
            $this->utente = User::create($payload);
            Audit::registra('crea_utente', $this->utente);
            $messaggio = 'Utente creato.';
        }

        session()->flash('success', $messaggio);
        $this->redirectRoute('utenti.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.utenti.modifica', [
            'ruoli' => RuoloUtente::cases(),
        ]);
    }
}
