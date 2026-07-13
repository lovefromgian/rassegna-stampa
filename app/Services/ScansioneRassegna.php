<?php

namespace App\Services;

use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Models\Rassegna;
use App\Models\Testata;
use App\Models\Uscita;
use App\Support\Discovery\ArticleDiscoverySource;
use App\Support\Discovery\ArticoloTrovato;
use App\Support\Discovery\RichiestaScoperta;
use Illuminate\Support\Str;

/**
 * Scansione di una rassegna: interroga la fonte di scoperta con le parole chiave e crea le
 * nuove uscite candidato (regole-business.md §2-3). Applica:
 *  - esclusioni: un termine escluso presente scarta il candidato (falso positivo);
 *  - deduplica: una URL già presente nella rassegna non si ripropone (nemmeno se scartata);
 *  - punteggio 0-100: quante parole chiave richieste compaiono (aiuto all'ordinamento, non
 *    una decisione: la parola finale è dell'operatore).
 */
class ScansioneRassegna
{
    public function __construct(private ArticleDiscoverySource $source) {}

    /**
     * @return int numero di nuovi candidati creati
     */
    public function scansiona(Rassegna $rassegna): int
    {
        $articoli = $this->source->cerca(new RichiestaScoperta(
            paroleChiave: array_values($rassegna->parole_chiave ?? []),
            paroleEscluse: array_values($rassegna->parole_escluse ?? []),
            da: $rassegna->monitoraggio_inizio,
            a: $rassegna->monitoraggio_fine,
        ));

        // Deduplica su tutte le URL già viste nella rassegna, comprese scartate/eliminate.
        $urlEsistenti = $rassegna->uscite()->withTrashed()->pluck('url')->filter()->all();
        $urlEsistenti = array_flip($urlEsistenti);

        $nuovi = 0;

        foreach ($articoli as $articolo) {
            if (isset($urlEsistenti[$articolo->url])) {
                continue; // già presente: non si ripropone
            }
            if ($this->contieneEscluse($articolo, $rassegna->parole_escluse ?? [])) {
                continue; // termine escluso: falso positivo tagliato alla fonte
            }

            $testata = Testata::firstOrCreate(
                ['nome' => $articolo->testata],
                ['tipo_prevalente' => TipoMedia::Online],
            );

            Uscita::create([
                'rassegna_id' => $rassegna->id,
                'testata_id' => $testata->id,
                'titolo' => $articolo->titolo,
                'data_pubblicazione' => $articolo->dataPubblicazione->toDateString(),
                'url' => $articolo->url,
                'tipo_media' => TipoMedia::Online,
                'stato' => StatoUscita::Candidato,
                'punteggio_corrispondenza' => $this->punteggio($articolo, $rassegna->parole_chiave ?? []),
                // Anteprima provvisoria: la cattura la sostituirà col testo completo.
                'testo_estratto' => $articolo->estratto,
                'data_rilevamento' => now(),
            ]);

            $urlEsistenti[$articolo->url] = true; // evita doppioni nello stesso lotto
            $nuovi++;
        }

        return $nuovi;
    }

    /**
     * @param  list<string>  $escluse
     */
    private function contieneEscluse(ArticoloTrovato $articolo, array $escluse): bool
    {
        $testo = Str::lower($articolo->titolo.' '.($articolo->estratto ?? ''));

        foreach ($escluse as $termine) {
            if ($termine !== '' && str_contains($testo, Str::lower($termine))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Punteggio 0-100: percentuale di parole chiave richieste presenti nel titolo/estratto.
     *
     * @param  list<string>  $chiavi
     */
    private function punteggio(ArticoloTrovato $articolo, array $chiavi): int
    {
        $chiavi = array_values(array_filter($chiavi, fn ($k) => trim($k) !== ''));
        if ($chiavi === []) {
            return 0;
        }

        $testo = Str::lower($articolo->titolo.' '.($articolo->estratto ?? ''));
        $presenti = 0;
        foreach ($chiavi as $chiave) {
            if (str_contains($testo, Str::lower(trim($chiave)))) {
                $presenti++;
            }
        }

        return (int) round($presenti / count($chiavi) * 100);
    }
}
