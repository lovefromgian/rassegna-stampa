<?php

namespace App\Policies;

use App\Models\LogAzione;
use App\Models\User;

/**
 * Permessi LogAzione (audit): consultabile da entrambi, IMMUTABILE per tutti.
 * Nessuno modifica né cancella il log, nemmeno il supervisore
 * (docs/modello-dati.md §User, regole-business.md §10-11). L'immutabilità è imposta
 * anche a livello di modello (App\Models\LogAzione), qui è ribadita in autorizzazione.
 */
class LogAzionePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LogAzione $log): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false; // il log lo scrive il sistema, non l'utente
    }

    public function update(User $user, LogAzione $log): bool
    {
        return false;
    }

    public function delete(User $user, LogAzione $log): bool
    {
        return false;
    }

    public function forceDelete(User $user, LogAzione $log): bool
    {
        return false;
    }
}
