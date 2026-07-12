<?php

namespace App\Enums;

enum RuoloUtente: string
{
    case Supervisore = 'supervisore';
    case Operatore = 'operatore';

    public function etichetta(): string
    {
        return match ($this) {
            self::Supervisore => 'Supervisore',
            self::Operatore => 'Operatore',
        };
    }
}
