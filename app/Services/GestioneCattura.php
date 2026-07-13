<?php

namespace App\Services;

use App\Enums\StatoCattura;
use App\Jobs\CatturaUscita;
use App\Models\Uscita;

/**
 * Punto unico per avviare (o ri-avviare) la cattura di un'uscita: prepara lo stato e
 * accoda il job. La cattura vera resta un job in coda (mai sincrona).
 */
class GestioneCattura
{
    /**
     * Accoda la cattura di un'uscita online. Ritorna true se accodata, false se l'uscita
     * non ha una pagina web da catturare (media manuale).
     */
    public function avvia(Uscita $uscita): bool
    {
        if (! $uscita->richiedeCatturaWeb()) {
            return false;
        }

        $uscita->update([
            'stato_cattura' => StatoCattura::InAttesa,
            'errore_cattura' => null,
        ]);

        CatturaUscita::dispatch($uscita);

        return true;
    }
}
