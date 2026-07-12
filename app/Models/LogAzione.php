<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * LogAzione (audit) — IMMUTABILE. Nessuno lo modifica né lo cancella, nemmeno il
 * supervisore (regole-business.md §11, CLAUDE.md §6). L'immutabilità è imposta qui a
 * livello di modello: qualsiasi update/delete lancia un'eccezione. Nessun updated_at.
 */
class LogAzione extends Model
{
    /** @use HasFactory<\Database\Factories\LogAzioneFactory> */
    use HasFactory;

    protected $table = 'log_azioni';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'azione',
        'entita_tipo',
        'entita_id',
        'dettagli',
    ];

    protected function casts(): array
    {
        return [
            'dettagli' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Il log di audit è immutabile: non può essere modificato.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Il log di audit è immutabile: non può essere cancellato.');
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
