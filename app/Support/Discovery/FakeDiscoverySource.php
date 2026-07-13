<?php

namespace App\Support\Discovery;

/**
 * Fonte di scoperta finta per i test: restituisce articoli predefiniti, nessuna rete.
 */
class FakeDiscoverySource implements ArticleDiscoverySource
{
    /**
     * @param  list<ArticoloTrovato>  $articoli
     */
    public function __construct(private array $articoli = []) {}

    public function cerca(RichiestaScoperta $richiesta): array
    {
        return $this->articoli;
    }
}
