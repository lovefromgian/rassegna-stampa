<?php

use App\Enums\StatoUscita;
use App\Models\Rassegna;
use App\Models\Testata;
use App\Models\Uscita;
use App\Services\ScansioneRassegna;
use App\Support\Discovery\ArticleDiscoverySource;
use App\Support\Discovery\ArticoloTrovato;
use App\Support\Discovery\FakeDiscoverySource;
use Illuminate\Support\Carbon;

function articolo(string $titolo, string $url, string $testata = 'Il Goriziano', string $estratto = ''): ArticoloTrovato
{
    return new ArticoloTrovato($titolo, $url, $testata, Carbon::parse('2026-06-27'), $estratto);
}

function scansionaCon(array $articoli, Rassegna $rassegna): int
{
    app()->instance(ArticleDiscoverySource::class, new FakeDiscoverySource($articoli));

    return app(ScansioneRassegna::class)->scansiona($rassegna);
}

test('la scansione crea candidati con testata e punteggio', function () {
    $rassegna = Rassegna::factory()->create(['parole_chiave' => ['Grado', 'musei'], 'parole_escluse' => []]);

    $nuovi = scansionaCon([
        articolo('Grado punta sulla cultura: musei e visite', 'https://n.gl/1', 'Il Goriziano', 'Estate a Grado'),
    ], $rassegna);

    expect($nuovi)->toBe(1);
    $uscita = Uscita::where('url', 'https://n.gl/1')->firstOrFail();
    expect($uscita->stato)->toBe(StatoUscita::Candidato)
        ->and($uscita->punteggio_corrispondenza)->toBe(100) // 'Grado' e 'musei' presenti
        ->and($uscita->testata->nome)->toBe('Il Goriziano');
    expect(Testata::where('nome', 'Il Goriziano')->count())->toBe(1);
});

test('il punteggio riflette quante parole chiave compaiono', function () {
    $rassegna = Rassegna::factory()->create(['parole_chiave' => ['Grado', 'musei'], 'parole_escluse' => []]);

    scansionaCon([
        articolo('Grado in festa per il patrono', 'https://n.gl/2'), // solo 'Grado'
    ], $rassegna);

    expect(Uscita::where('url', 'https://n.gl/2')->first()->punteggio_corrispondenza)->toBe(50);
});

test('un falso positivo debole viene proposto con punteggio basso, non escluso', function () {
    $rassegna = Rassegna::factory()->create(['parole_chiave' => ['Grado', 'musei'], 'parole_escluse' => []]);

    // "Dieci gradi in meno" non contiene né 'Grado' né 'musei' come sottostringa.
    scansionaCon([articolo('Dieci gradi in meno: dove rifugiarsi dal caldo', 'https://n.gl/3')], $rassegna);

    $uscita = Uscita::where('url', 'https://n.gl/3')->first();
    expect($uscita)->not->toBeNull()
        ->and($uscita->punteggio_corrispondenza)->toBe(0);
});

test('un termine escluso taglia il candidato', function () {
    $rassegna = Rassegna::factory()->create(['parole_chiave' => ['Grado'], 'parole_escluse' => ['gradi']]);

    $nuovi = scansionaCon([
        articolo('Dieci gradi in meno in Friuli', 'https://n.gl/4'),
        articolo('Grado punta sulla cultura', 'https://n.gl/5'),
    ], $rassegna);

    expect($nuovi)->toBe(1);
    expect(Uscita::where('url', 'https://n.gl/4')->exists())->toBeFalse();
    expect(Uscita::where('url', 'https://n.gl/5')->exists())->toBeTrue();
});

test('la deduplica non ripropone una URL già presente, nemmeno se scartata', function () {
    $rassegna = Rassegna::factory()->create(['parole_chiave' => ['Grado'], 'parole_escluse' => []]);
    Uscita::factory()->for($rassegna)->scartato()->create(['url' => 'https://n.gl/6']);

    $nuovi = scansionaCon([articolo('Grado, articolo già scartato', 'https://n.gl/6')], $rassegna);

    expect($nuovi)->toBe(0);
    expect(Uscita::where('url', 'https://n.gl/6')->count())->toBe(1);
});

test('due scansioni successive non duplicano lo stesso articolo', function () {
    $rassegna = Rassegna::factory()->create(['parole_chiave' => ['Grado'], 'parole_escluse' => []]);
    $articoli = [articolo('Grado sul giornale', 'https://n.gl/7')];

    expect(scansionaCon($articoli, $rassegna))->toBe(1);
    expect(scansionaCon($articoli, $rassegna))->toBe(0);
    expect(Uscita::where('url', 'https://n.gl/7')->count())->toBe(1);
});
