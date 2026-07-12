<?php

namespace App\Enums;

/**
 * Stati della Rassegna (docs/modello-dati.md).
 *
 *   in_raccolta ──> in_revisione ──> chiusa ──> riaperta ──┐
 *        ▲                                                  │
 *        └──────────────────────────────────────────────────┘
 */
enum StatoRassegna: string
{
    case InRaccolta = 'in_raccolta';
    case InRevisione = 'in_revisione';
    case Chiusa = 'chiusa';
    case Riaperta = 'riaperta';

    public function etichetta(): string
    {
        return match ($this) {
            self::InRaccolta => 'In raccolta',
            self::InRevisione => 'In revisione',
            self::Chiusa => 'Chiusa',
            self::Riaperta => 'Riaperta',
        };
    }
}
