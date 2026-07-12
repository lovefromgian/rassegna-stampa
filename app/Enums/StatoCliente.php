<?php

namespace App\Enums;

enum StatoCliente: string
{
    case Attivo = 'attivo';
    case Archiviato = 'archiviato';

    public function etichetta(): string
    {
        return match ($this) {
            self::Attivo => 'Attivo',
            self::Archiviato => 'Archiviato',
        };
    }
}
