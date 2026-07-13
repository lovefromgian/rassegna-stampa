<?php

namespace App\Support\Capture;

/**
 * Motore di cattura di una pagina web, dietro interfaccia astratta.
 * L'implementazione attuale è Playwright/Chromium (App\Support\Capture\PlaywrightCapturer),
 * ma deve essere sostituibile (altro browser, servizio esterno) senza toccare il resto
 * del codice — stesso principio della fonte di scoperta (CLAUDE.md §4).
 *
 * La cattura è SEMPRE invocata da un job in coda, mai sincrona nella richiesta HTTP.
 */
interface PageCapturer
{
    /**
     * Cattura la pagina all'URL indicato.
     *
     * @throws CapturaException se la cattura fallisce (messaggio leggibile)
     */
    public function cattura(string $url): CapturaRisultato;
}
