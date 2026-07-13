<?php

namespace App\Support\Discovery;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fonte di scoperta basata sul feed RSS di ricerca di Google News. Scelta pragmatica per
 * partire: copre le testate iperlocali FVG meglio delle API a pagamento. Sostituibile
 * (è dietro ArticleDiscoverySource).
 *
 * Nota: il feed restituisce link di redirect news.google.com; l'URL reale si risolve
 * aprendo il link (la cattura Playwright segue il redirect). Vedi TECH-DEBT TD-004.
 */
class GoogleNewsRss implements ArticleDiscoverySource
{
    public function __construct(
        private string $baseUrl = 'https://news.google.com/rss/search',
        private int $timeout = 20,
    ) {}

    public function cerca(RichiestaScoperta $richiesta): array
    {
        $risposta = Http::timeout($this->timeout)->get($this->baseUrl, [
            'q' => $this->query($richiesta),
            'hl' => 'it',
            'gl' => 'IT',
            'ceid' => 'IT:it',
        ]);

        if (! $risposta->successful()) {
            return [];
        }

        return $this->parse($risposta->body(), $richiesta);
    }

    private function query(RichiestaScoperta $richiesta): string
    {
        $parti = array_map(fn ($k) => str_contains($k, ' ') ? '"'.$k.'"' : $k, $richiesta->paroleChiave);

        foreach ($richiesta->paroleEscluse as $escl) {
            $parti[] = '-'.(str_contains($escl, ' ') ? '"'.$escl.'"' : $escl);
        }

        return implode(' ', $parti);
    }

    /**
     * @return list<ArticoloTrovato>
     */
    private function parse(string $xml, RichiestaScoperta $richiesta): array
    {
        try {
            $rss = new \SimpleXMLElement($xml);
        } catch (Throwable) {
            return [];
        }

        $articoli = [];
        foreach ($rss->channel->item ?? [] as $item) {
            try {
                $data = Carbon::parse((string) $item->pubDate);
            } catch (Throwable) {
                continue;
            }

            // Filtro locale sulla finestra di monitoraggio.
            if ($data->lt($richiesta->da->copy()->startOfDay()) || $data->gt($richiesta->a->copy()->endOfDay())) {
                continue;
            }

            $titoloGrezzo = trim((string) $item->title);
            $testata = trim((string) ($item->source ?? ''));

            // Il titolo Google News è spesso "Titolo - Testata": separa se serve.
            if ($testata === '' && str_contains($titoloGrezzo, ' - ')) {
                $testata = trim(Str::afterLast($titoloGrezzo, ' - '));
            }
            $titolo = $testata !== '' && str_ends_with($titoloGrezzo, ' - '.$testata)
                ? trim(Str::beforeLast($titoloGrezzo, ' - '.$testata))
                : $titoloGrezzo;

            $articoli[] = new ArticoloTrovato(
                titolo: $titolo,
                url: trim((string) $item->link),
                testata: $testata !== '' ? $testata : 'Sconosciuta',
                dataPubblicazione: $data,
                estratto: $this->testoPiano((string) $item->description),
            );
        }

        return $articoli;
    }

    private function testoPiano(string $html): ?string
    {
        $testo = trim(html_entity_decode(strip_tags($html)));

        return $testo !== '' ? Str::limit($testo, 400) : null;
    }
}
