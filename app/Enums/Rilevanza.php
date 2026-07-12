<?php

namespace App\Enums;

/**
 * Rilevanza dell'uscita, assegnata in revisione (docs/regole-business.md §5).
 * Determina l'ordinamento proposto nel PDF: principale → secondaria → citazione.
 */
enum Rilevanza: string
{
    case Principale = 'principale';
    case Secondaria = 'secondaria';
    case Citazione = 'citazione';

    public function etichetta(): string
    {
        return match ($this) {
            self::Principale => 'Principale',
            self::Secondaria => 'Secondaria',
            self::Citazione => 'Citazione',
        };
    }

    /** Peso per l'ordinamento proposto nel PDF (più basso = più in alto). */
    public function peso(): int
    {
        return match ($this) {
            self::Principale => 0,
            self::Secondaria => 1,
            self::Citazione => 2,
        };
    }
}
