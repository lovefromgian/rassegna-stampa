<?php

namespace App\Support\Capture;

/**
 * Motore di cattura finto: nessuna rete, nessun Chromium. Usato nei test per esercitare
 * il job e il flusso senza dipendere dall'ambiente. Configurabile per simulare un errore.
 */
class FakeCapturer implements PageCapturer
{
    public function __construct(
        private ?string $erroreDaLanciare = null,
        private string $testo = 'Testo di prova estratto dalla pagina catturata.',
        private string $titolo = 'Titolo di prova',
    ) {}

    public function cattura(string $url): CapturaRisultato
    {
        if ($this->erroreDaLanciare !== null) {
            throw new CapturaException($this->erroreDaLanciare);
        }

        // PNG 1x1 valido, sufficiente per i test di persistenza dello screenshot.
        $pngMinimo = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        return new CapturaRisultato(
            screenshot: $pngMinimo,
            testoEstratto: $this->testo,
            urlFinale: $url,
            pdfPagina: '%PDF-1.4 fake',
            titolo: $this->titolo,
        );
    }
}
