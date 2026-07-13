<?php

namespace App\Services;

use App\Enums\StatoUscita;
use App\Models\Rassegna;

/**
 * Blocchi alla generazione del PDF (regole-business.md §7). Il PDF si genera SOLO se:
 *   1. nessuna uscita resta in stato `candidato` (l'operatore ha deciso su tutte);
 *   2. ogni uscita `approvato` ha un materiale valido (screenshot o file caricato).
 *
 * L'interfaccia dice PERCHÉ è bloccata, non si limita a disabilitare il pulsante.
 */
class BlocchiGenerazione
{
    /**
     * @return list<string> elenco dei motivi che bloccano la generazione (vuoto = si può)
     */
    public function motivi(Rassegna $rassegna): array
    {
        $motivi = [];

        $candidati = $rassegna->uscite()->where('stato', StatoUscita::Candidato)->count();
        if ($candidati > 0) {
            $motivi[] = "Ci sono {$candidati} uscite ancora da confermare o scartare (stato candidato).";
        }

        $approvate = $rassegna->uscite()->where('stato', StatoUscita::Approvato)->get();
        $senzaMateriale = $approvate->filter(fn ($u) => ! $u->haMaterialeValido())->count();
        if ($senzaMateriale > 0) {
            $motivi[] = "{$senzaMateriale} uscite approvate non hanno uno screenshot valido né un file caricato.";
        }

        if ($approvate->isEmpty()) {
            $motivi[] = 'Non c\'è nessuna uscita approvata da includere nel PDF.';
        }

        return $motivi;
    }

    public function puoGenerare(Rassegna $rassegna): bool
    {
        return $this->motivi($rassegna) === [];
    }
}
