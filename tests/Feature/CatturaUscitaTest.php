<?php

use App\Enums\StatoCattura;
use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Jobs\CatturaUscita;
use App\Models\Uscita;
use App\Services\GestioneCattura;
use App\Support\Capture\FakeCapturer;
use App\Support\Capture\PageCapturer;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('la cattura riuscita produce screenshot, testo e porta l\'uscita a catturato', function () {
    Storage::fake('public');
    app()->instance(PageCapturer::class, new FakeCapturer(testo: 'Grado punta sulla cultura: musei e visite.'));

    $uscita = Uscita::factory()->confermato()->create([
        'tipo_media' => TipoMedia::Online,
        'url' => 'https://ilgoriziano.it/grado-cultura',
        'stato_cattura' => StatoCattura::InAttesa,
    ]);

    CatturaUscita::dispatchSync($uscita);

    $uscita->refresh();
    expect($uscita->stato)->toBe(StatoUscita::Catturato)
        ->and($uscita->stato_cattura)->toBe(StatoCattura::Completata)
        ->and($uscita->errore_cattura)->toBeNull()
        ->and($uscita->testo_estratto)->toContain('Grado punta sulla cultura')
        ->and($uscita->cattura_completata_il)->not->toBeNull();

    Storage::disk('public')->assertExists($uscita->screenshot_path);
    Storage::disk('public')->assertExists($uscita->pdf_pagina_path);
});

test('la cattura fallita salva un errore leggibile e lascia l\'uscita confermato', function () {
    Storage::fake('public');
    app()->instance(PageCapturer::class, new FakeCapturer(erroreDaLanciare: 'Timeout: la pagina non ha risposto in 60s.'));

    $uscita = Uscita::factory()->confermato()->create([
        'tipo_media' => TipoMedia::Online,
        'url' => 'https://esempio.it/lenta',
        'stato_cattura' => StatoCattura::InAttesa,
    ]);

    CatturaUscita::dispatchSync($uscita);

    $uscita->refresh();
    expect($uscita->stato)->toBe(StatoUscita::Confermato)
        ->and($uscita->stato_cattura)->toBe(StatoCattura::Errore)
        ->and($uscita->errore_cattura)->toContain('Timeout')
        ->and($uscita->screenshot_path)->toBeNull();
});

test('la cattura non tocca le uscite manuali (niente pagina web)', function () {
    app()->instance(PageCapturer::class, new FakeCapturer);

    $uscita = Uscita::factory()->manuale()->confermato()->create();

    CatturaUscita::dispatchSync($uscita);

    $uscita->refresh();
    expect($uscita->stato)->toBe(StatoUscita::Confermato)
        ->and($uscita->stato_cattura)->toBeNull();
});

test('GestioneCattura accoda il job e mette l\'uscita in attesa', function () {
    Queue::fake();

    $uscita = Uscita::factory()->confermato()->create([
        'tipo_media' => TipoMedia::Online,
        'url' => 'https://esempio.it/a',
        'errore_cattura' => 'vecchio errore',
    ]);

    $accodata = app(GestioneCattura::class)->avvia($uscita);

    expect($accodata)->toBeTrue()
        ->and($uscita->fresh()->stato_cattura)->toBe(StatoCattura::InAttesa)
        ->and($uscita->fresh()->errore_cattura)->toBeNull();

    Queue::assertPushed(CatturaUscita::class);
});

test('GestioneCattura non accoda nulla per un\'uscita manuale', function () {
    Queue::fake();

    $uscita = Uscita::factory()->manuale()->confermato()->create();

    expect(app(GestioneCattura::class)->avvia($uscita))->toBeFalse();

    Queue::assertNothingPushed();
});
