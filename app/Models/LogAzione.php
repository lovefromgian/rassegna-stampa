<?php

namespace App\Models;

use Database\Factories\LogAzioneFactory;
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
    /** @use HasFactory<LogAzioneFactory> */
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

    /** Etichetta leggibile dell'azione. */
    public function etichetta(): string
    {
        return match ($this->azione) {
            'crea_cliente' => 'Creazione cliente',
            'modifica_cliente' => 'Modifica cliente',
            'crea_rassegna' => 'Creazione rassegna',
            'modifica_rassegna' => 'Modifica rassegna',
            'chiude_raccolta' => 'Chiusura raccolta',
            'chiude_rassegna' => 'Chiusura rassegna',
            'riapre_rassegna' => 'Riapertura rassegna',
            'conferma_candidato' => 'Conferma candidato',
            'approva_uscita' => 'Approvazione uscita',
            'scarto_uscita' => 'Scarto uscita',
            'genera_pdf' => 'Generazione PDF',
            'scarica_pdf' => 'Download PDF',
            default => ucfirst(str_replace('_', ' ', $this->azione)),
        };
    }

    /** Categoria per la pill di stato nella UI. */
    public function categoria(): string
    {
        return match ($this->azione) {
            'genera_pdf', 'scarica_pdf' => 'accent',
            'approva_uscita' => 'success',
            'scarto_uscita' => 'warning',
            'riapre_rassegna', 'chiude_rassegna', 'chiude_raccolta' => 'neutral',
            default => 'neutral',
        };
    }
}
