<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

/**
 * Permessi Cliente (docs/modello-dati.md §User):
 * supervisore crea/modifica/elimina; operatore sola lettura.
 * Applicati lato server: un vincolo solo-UI non è fatto (regole-business.md preambolo).
 */
class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // ogni utente vede tutti i clienti dell'agenzia
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isSupervisore();
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->isSupervisore();
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->isSupervisore();
    }

    public function restore(User $user, Cliente $cliente): bool
    {
        return $user->isSupervisore();
    }

    /**
     * Cancellazione definitiva dal cestino: solo supervisore (deroga autorizzata alla
     * regola "nulla si cancella fisicamente", vedi regole-business.md §10 e TECH-DEBT).
     * Irreversibile: rimuove il cliente e, a cascata, le sue rassegne/uscite e i relativi file.
     */
    public function forceDelete(User $user, Cliente $cliente): bool
    {
        return $user->isSupervisore();
    }
}
