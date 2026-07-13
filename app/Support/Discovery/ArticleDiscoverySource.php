<?php

namespace App\Support\Discovery;

/**
 * Fonte di scoperta degli articoli, dietro interfaccia astratta (CLAUDE.md §4,
 * regole-business.md §2). L'implementazione attuale è Google News/RSS, ma deve essere
 * sostituibile con un'API a pagamento o un servizio professionale senza toccare il resto
 * del codice.
 */
interface ArticleDiscoverySource
{
    /**
     * Cerca gli articoli che corrispondono alla richiesta.
     *
     * @return list<ArticoloTrovato>
     */
    public function cerca(RichiestaScoperta $richiesta): array;
}
