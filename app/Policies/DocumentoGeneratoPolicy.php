<?php

namespace App\Policies;

use App\Models\DocumentoGenerato;
use App\Models\User;

/**
 * Permessi DocumentoGenerato (PDF): generazione e download per entrambi i ruoli.
 * Il PDF è versionato e non si sovrascrive né si cancella (CLAUDE.md §6).
 */
class DocumentoGeneratoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DocumentoGenerato $documento): bool
    {
        return true;
    }

    /** Generazione del PDF: entrambi (il blocco di merito è nelle regole §7). */
    public function create(User $user): bool
    {
        return true;
    }

    /** Download del PDF: entrambi. */
    public function download(User $user, DocumentoGenerato $documento): bool
    {
        return true;
    }

    /** Un PDF generato non si modifica: si crea una nuova versione. */
    public function update(User $user, DocumentoGenerato $documento): bool
    {
        return false;
    }

    public function delete(User $user, DocumentoGenerato $documento): bool
    {
        return false;
    }

    public function forceDelete(User $user, DocumentoGenerato $documento): bool
    {
        return false;
    }
}
