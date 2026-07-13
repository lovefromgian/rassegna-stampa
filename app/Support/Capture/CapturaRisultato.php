<?php

namespace App\Support\Capture;

/**
 * Esito di una cattura riuscita: i contenuti binari degli artefatti + testo e metadati.
 * Il motore di cattura NON scrive sullo storage durevole: restituisce i byte, e il job
 * li persiste tramite il disco Laravel (regole-business.md §12, storage astratto).
 */
readonly class CapturaRisultato
{
    public function __construct(
        /** PNG full-page: l'immagine che finisce nel PDF. */
        public string $screenshot,
        /** Testo estratto dell'articolo (indicizzato per la ricerca d'archivio). */
        public string $testoEstratto,
        /** URL finale dopo eventuali redirect. */
        public string $urlFinale,
        /** PDF multipagina leggibile della pagina (facoltativo). */
        public ?string $pdfPagina = null,
        /** Titolo della pagina catturata (facoltativo). */
        public ?string $titolo = null,
    ) {}
}
