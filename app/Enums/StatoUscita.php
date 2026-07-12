<?php

namespace App\Enums;

/**
 * Stati dell'Uscita (docs/modello-dati.md).
 *
 *   candidato ──> confermato ──> catturato ──> approvato
 *       │              │              │
 *       └──────────────┴──────────────┴──────> scartato
 */
enum StatoUscita: string
{
    case Candidato = 'candidato';
    case Confermato = 'confermato';
    case Catturato = 'catturato';
    case Approvato = 'approvato';
    case Scartato = 'scartato';

    public function etichetta(): string
    {
        return match ($this) {
            self::Candidato => 'Candidato',
            self::Confermato => 'Confermato',
            self::Catturato => 'Catturato',
            self::Approvato => 'Approvato',
            self::Scartato => 'Scartato',
        };
    }
}
