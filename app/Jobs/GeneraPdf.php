<?php

namespace App\Jobs;

use App\Models\Rassegna;
use App\Models\User;
use App\Services\Audit;
use App\Services\GeneratorePdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Genera una nuova versione del PDF della rassegna in coda (CLAUDE.md §2: la generazione
 * PDF è lavoro del queue worker). I blocchi §7 vanno verificati PRIMA di accodare, così
 * l'operatore ha un motivo immediato; il generatore li ri-controlla come rete di sicurezza.
 */
class GeneraPdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public Rassegna $rassegna,
        public User $autore,
    ) {}

    public function handle(GeneratorePdf $generatore): void
    {
        $documento = $generatore->genera($this->rassegna, $this->autore);

        Audit::registra('genera_pdf', $documento, ['versione' => $documento->versione], $this->autore);
    }
}
