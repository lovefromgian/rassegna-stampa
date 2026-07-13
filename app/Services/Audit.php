<?php

namespace App\Services;

use App\Models\LogAzione;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Registra le azioni rilevanti nel log di audit immutabile (regole-business.md §11).
 * L'attore è l'utente autenticato; nei job in coda va passato esplicitamente. Il log
 * non si modifica né si cancella. Solo per azioni con un "chi": eventi automatici (es.
 * la cattura) non passano di qui.
 */
class Audit
{
    /**
     * @param  array<string, mixed>  $dettagli
     */
    public static function registra(string $azione, ?Model $entita = null, array $dettagli = [], ?User $attore = null): LogAzione
    {
        return LogAzione::create([
            'user_id' => $attore?->id ?? Auth::id(),
            'azione' => $azione,
            'entita_tipo' => $entita ? $entita::class : 'sistema',
            'entita_id' => $entita?->getKey(),
            'dettagli' => $dettagli ?: null,
        ]);
    }
}
