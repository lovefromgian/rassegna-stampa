<?php

namespace App\Support\Capture;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Cattura via Playwright/Chromium headless, invocato come processo Node esterno
 * (scripts/capture.js). Il PHP orchestra: prepara una cartella temporanea, lancia lo
 * script, raccoglie gli artefatti e li restituisce come byte (li persiste il job).
 *
 * Lo script gestisce cookie banner, scroll dei contenuti lazy, attesa networkidle e
 * blocco dei domini pubblicitari (regole-business.md §4).
 */
class PlaywrightCapturer implements PageCapturer
{
    public function __construct(
        private string $nodeBinary = 'node',
        private ?string $scriptPath = null,
        private int $timeoutSecondi = 120,
    ) {
        $this->scriptPath ??= base_path('scripts/capture.cjs');
    }

    public function cattura(string $url): CapturaRisultato
    {
        if (! File::exists($this->scriptPath)) {
            throw new CapturaException("Script di cattura non trovato: {$this->scriptPath}");
        }

        $tmp = storage_path('app/capture-tmp/'.Str::uuid());
        File::ensureDirectoryExists($tmp);

        try {
            $result = Process::timeout($this->timeoutSecondi)->run([
                $this->nodeBinary,
                $this->scriptPath,
                '--url='.$url,
                '--out='.$tmp,
            ]);

            if (! $result->successful()) {
                $dettaglio = trim($result->errorOutput()) ?: trim($result->output()) ?: 'nessun dettaglio';
                throw new CapturaException("Cattura fallita (codice {$result->exitCode()}): {$dettaglio}");
            }

            $meta = json_decode($result->output(), true);
            if (! is_array($meta) || ($meta['ok'] ?? false) !== true) {
                $errore = is_array($meta) ? ($meta['error'] ?? 'output non valido') : 'output non interpretabile';
                throw new CapturaException("Cattura fallita: {$errore}");
            }

            $screenshotFile = $tmp.'/screenshot.png';
            if (! File::exists($screenshotFile)) {
                throw new CapturaException('Cattura fallita: screenshot non prodotto.');
            }

            $pdfFile = $tmp.'/page.pdf';
            $textFile = $tmp.'/text.txt';

            return new CapturaRisultato(
                screenshot: File::get($screenshotFile),
                testoEstratto: File::exists($textFile) ? File::get($textFile) : '',
                urlFinale: $meta['finalUrl'] ?? $url,
                pdfPagina: File::exists($pdfFile) ? File::get($pdfFile) : null,
                titolo: $meta['title'] ?? null,
            );
        } finally {
            File::deleteDirectory($tmp);
        }
    }
}
