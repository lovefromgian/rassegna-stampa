<?php

namespace App\Console\Commands;

use App\Enums\StatoRassegna;
use App\Jobs\ScansionaRassegna;
use App\Models\Rassegna;
use Illuminate\Console\Command;

/**
 * Scansione giornaliera automatica: accoda una scansione per ogni rassegna in raccolta con
 * periodo di monitoraggio attivo (regole-business.md §2). Schedulato una volta al giorno.
 */
class ScansionaRassegneAttive extends Command
{
    protected $signature = 'rassegne:scansiona';

    protected $description = 'Accoda la scansione delle rassegne in raccolta con periodo di monitoraggio attivo';

    public function handle(): int
    {
        $rassegne = Rassegna::query()
            ->where('stato', StatoRassegna::InRaccolta)
            ->conPeriodoAttivo()
            ->get();

        foreach ($rassegne as $rassegna) {
            ScansionaRassegna::dispatch($rassegna);
        }

        $this->info("Accodate {$rassegne->count()} scansioni.");

        return self::SUCCESS;
    }
}
