<?php

use App\Support\Discovery\GoogleNewsRss;
use App\Support\Discovery\RichiestaScoperta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

function rssFinto(): string
{
    return <<<'XML'
    <?xml version="1.0"?>
    <rss version="2.0"><channel>
      <item>
        <title>Grado punta sulla cultura - Il Goriziano</title>
        <link>https://news.google.com/rss/articles/AAA</link>
        <pubDate>Sat, 27 Jun 2026 08:00:00 GMT</pubDate>
        <description>&lt;a href="x"&gt;Estate 2026 a Grado&lt;/a&gt; si punta sulla cultura</description>
        <source url="https://ilgoriziano.it">Il Goriziano</source>
      </item>
      <item>
        <title>Vecchia notizia fuori periodo - Testata X</title>
        <link>https://news.google.com/rss/articles/BBB</link>
        <pubDate>Mon, 01 Jan 2026 08:00:00 GMT</pubDate>
        <source url="https://x.it">Testata X</source>
      </item>
    </channel></rss>
    XML;
}

test('Google News RSS interroga l\'endpoint con parole richieste ed escluse', function () {
    Http::fake(['news.google.com/*' => Http::response(rssFinto())]);

    $richiesta = new RichiestaScoperta(
        paroleChiave: ['Grado', 'musei'],
        paroleEscluse: ['gradi'],
        da: Carbon::parse('2026-06-25'),
        a: Carbon::parse('2026-07-09'),
    );

    app(GoogleNewsRss::class)->cerca($richiesta);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'news.google.com')
            && str_contains(urldecode($request->url()), 'Grado musei -gradi');
    });
});

test('il parsing estrae titolo, testata, data e filtra fuori periodo', function () {
    Http::fake(['news.google.com/*' => Http::response(rssFinto())]);

    $articoli = app(GoogleNewsRss::class)->cerca(new RichiestaScoperta(
        paroleChiave: ['Grado'],
        paroleEscluse: [],
        da: Carbon::parse('2026-06-25'),
        a: Carbon::parse('2026-07-09'),
    ));

    expect($articoli)->toHaveCount(1); // la notizia del 1 gennaio è fuori periodo
    $a = $articoli[0];
    expect($a->titolo)->toBe('Grado punta sulla cultura')
        ->and($a->testata)->toBe('Il Goriziano')
        ->and($a->url)->toBe('https://news.google.com/rss/articles/AAA')
        ->and($a->dataPubblicazione->format('Y-m-d'))->toBe('2026-06-27')
        ->and($a->estratto)->toContain('Estate 2026 a Grado');
});

test('una risposta di errore non fa esplodere la scoperta', function () {
    Http::fake(['news.google.com/*' => Http::response('', 503)]);

    $articoli = app(GoogleNewsRss::class)->cerca(new RichiestaScoperta(['Grado'], [], Carbon::parse('2026-06-25'), Carbon::parse('2026-07-09')));

    expect($articoli)->toBe([]);
});
