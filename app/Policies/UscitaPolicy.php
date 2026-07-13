<?php

namespace App\Policies;

use App\Models\Uscita;
use App\Models\User;

/**
 * Permessi Uscita (docs/modello-dati.md §User):
 * conferma/scarto candidati, revisione e approvazione: entrambi i ruoli.
 * Lo "scarto" è un cambio di stato (update), non una cancellazione: l'uscita
 * scartata resta archiviata e recuperabile (soft delete solo dal supervisore).
 */
class UscitaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Uscita $uscita): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true; // aggiunta manuale: entrambi
    }

    /** Conferma, scarto (stato), revisione, approvazione: entrambi. */
    public function update(User $user, Uscita $uscita): bool
    {
        return true;
    }

    /** Cancellazione del record: solo supervisore (lo scarto normale è un update). */
    public function delete(User $user, Uscita $uscita): bool
    {
        return $user->isSupervisore();
    }

    public function restore(User $user, Uscita $uscita): bool
    {
        return true; // recuperare un'uscita scartata è parte del flusso
    }

    /** Cancellazione definitiva dal cestino: solo supervisore (deroga §10). Irreversibile. */
    public function forceDelete(User $user, Uscita $uscita): bool
    {
        return $user->isSupervisore();
    }
}
