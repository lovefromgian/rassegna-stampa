<?php

namespace App\Support\Discovery;

use Illuminate\Support\Carbon;

/**
 * Parametri di una ricerca di articoli: parole chiave richieste ed escluse, e la finestra
 * temporale (il periodo di monitoraggio della rassegna). regole-business.md §2.
 */
readonly class RichiestaScoperta
{
    /**
     * @param  list<string>  $paroleChiave  termini richiesti
     * @param  list<string>  $paroleEscluse  termini che tagliano i falsi positivi
     */
    public function __construct(
        public array $paroleChiave,
        public array $paroleEscluse,
        public Carbon $da,
        public Carbon $a,
    ) {}
}
