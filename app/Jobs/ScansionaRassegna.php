<?php

namespace App\Jobs;

use App\Models\Rassegna;
use App\Services\ScansioneRassegna;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scansione di una rassegna in coda: la scoperta interroga la rete, quindi non è mai
 * sincrona nella richiesta HTTP. Usata sia dalla scansione giornaliera schedulata sia da
 * quella manuale.
 */
class ScansionaRassegna implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Rassegna $rassegna) {}

    public function handle(ScansioneRassegna $scansione): void
    {
        $scansione->scansiona($this->rassegna);
    }
}
