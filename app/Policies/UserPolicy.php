<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestione degli utenti: riservata al supervisore (docs/modello-dati.md §User).
 * Gli utenti non si cancellano: si disattivano (attivo = false).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSupervisore();
    }

    public function create(User $user): bool
    {
        return $user->isSupervisore();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isSupervisore();
    }

    /** Attivazione/disattivazione: supervisore, ma non sul proprio account. */
    public function attivazione(User $user, User $target): bool
    {
        return $user->isSupervisore() && $user->id !== $target->id;
    }
}
