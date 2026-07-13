<?php

namespace App\Support\Discovery;

use Illuminate\Support\Carbon;

/**
 * Un articolo grezzo restituito dalla fonte di scoperta, prima di ogni valutazione.
 * Il punteggio e la deduplica li calcola il servizio di scansione, non la fonte.
 */
readonly class ArticoloTrovato
{
    public function __construct(
        public string $titolo,
        public string $url,
        public string $testata,
        public Carbon $dataPubblicazione,
        public ?string $estratto = null,
    ) {}
}
