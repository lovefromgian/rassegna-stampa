<?php

namespace App\Enums;

/**
 * Stato tecnico della cattura di una URL (CLAUDE.md §4: "ogni cattura ha uno stato
 * esplicito e un errore leggibile in caso di fallimento").
 *
 * È un sotto-processo tecnico che gira mentre l'Uscita è `confermato`: al successo la
 * porta a `catturato` (App\Enums\StatoUscita). Resta `null` per le uscite che non hanno
 * una pagina web da fotografare (carta, radio, TV, agenzia: materiale caricato a mano).
 *
 *   in_attesa ──> in_corso ──> completata
 *                     │
 *                     └──────> errore
 */
enum StatoCattura: string
{
    case InAttesa = 'in_attesa';
    case InCorso = 'in_corso';
    case Completata = 'completata';
    case Errore = 'errore';

    public function etichetta(): string
    {
        return match ($this) {
            self::InAttesa => 'In attesa di cattura',
            self::InCorso => 'In cattura',
            self::Completata => 'Cattura completata',
            self::Errore => 'Errore di cattura',
        };
    }
}
