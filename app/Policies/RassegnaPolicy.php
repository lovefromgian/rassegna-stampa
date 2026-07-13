<?php

namespace App\Policies;

use App\Models\Rassegna;
use App\Models\User;

/**
 * Permessi Rassegna (docs/modello-dati.md §User):
 * supervisore tutto; operatore crea/modifica ma NON elimina né riapre.
 */
class RassegnaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rassegna $rassegna): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true; // operatore e supervisore
    }

    public function update(User $user, Rassegna $rassegna): bool
    {
        return true; // operatore e supervisore
    }

    /** Eliminazione (anche di rassegna inviata): solo supervisore. */
    public function delete(User $user, Rassegna $rassegna): bool
    {
        return $user->isSupervisore();
    }

    public function restore(User $user, Rassegna $rassegna): bool
    {
        return $user->isSupervisore();
    }

    /** Cancellazione definitiva dal cestino: solo supervisore (deroga §10). Irreversibile. */
    public function forceDelete(User $user, Rassegna $rassegna): bool
    {
        return $user->isSupervisore();
    }

    /** Riapertura di una rassegna chiusa: solo supervisore (regole-business.md §9). */
    public function riapri(User $user, Rassegna $rassegna): bool
    {
        return $user->isSupervisore();
    }
}
