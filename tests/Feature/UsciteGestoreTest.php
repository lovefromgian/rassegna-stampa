<?php

use App\Enums\StatoCattura;
use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Jobs\CatturaUscita;
use App\Livewire\Uscite\Gestore;
use App\Models\Rassegna;
use App\Models\Testata;
use App\Models\Uscita;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::actingAs(User::factory()->operatore()->create());
});

test('la scheda rassegna monta il gestore uscite', function () {
    $rassegna = Rassegna::factory()->create();

    $this->actingAs(User::factory()->operatore()->create())
        ->get(route('rassegne.show', $rassegna))
        ->assertOk()
        ->assertSee('Uscite raccolte');
});

test('aggiunta manuale di un\'uscita online: crea testata, uscita confermata e accoda la cattura', function () {
    Queue::fake();
    $rassegna = Rassegna::factory()->create();

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->call('nuovaUscita')
        ->set('tipo_media', TipoMedia::Online->value)
        ->set('titolo', 'Grado punta sulla cultura')
        ->set('testata_nome', 'Il Goriziano')
        ->set('data_pubblicazione', '2026-06-25')
        ->set('url', 'https://ilgoriziano.it/grado-cultura')
        ->call('salvaUscita')
        ->assertHasNoErrors();

    $uscita = Uscita::where('url', 'https://ilgoriziano.it/grado-cultura')->firstOrFail();
    expect($uscita->stato)->toBe(StatoUscita::Confermato)
        ->and($uscita->stato_cattura)->toBe(StatoCattura::InAttesa)
        ->and($uscita->testata->nome)->toBe('Il Goriziano')
        ->and(Testata::where('nome', 'Il Goriziano')->count())->toBe(1);

    Queue::assertPushed(CatturaUscita::class);
});

test('aggiunta manuale di un\'uscita cartacea con ritaglio: nessuna cattura, stato catturato', function () {
    Queue::fake();
    Storage::fake('public');
    $rassegna = Rassegna::factory()->create();

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->call('nuovaUscita')
        ->set('tipo_media', TipoMedia::Carta->value)
        ->set('titolo', 'Storia e tradizioni nei musei di Grado')
        ->set('testata_nome', 'Il Piccolo')
        ->set('data_pubblicazione', '2026-06-25')
        ->set('pagina_giornale', 'pag 55')
        ->set('fileRitaglio', UploadedFile::fake()->create('ritaglio.pdf', 200, 'application/pdf'))
        ->call('salvaUscita')
        ->assertHasNoErrors();

    $uscita = Uscita::where('titolo', 'Storia e tradizioni nei musei di Grado')->firstOrFail();
    expect($uscita->url)->toBeNull()
        ->and($uscita->tipo_media)->toBe(TipoMedia::Carta)
        ->and($uscita->stato)->toBe(StatoUscita::Catturato)
        ->and($uscita->file_caricato_path)->not->toBeNull();

    Storage::disk('public')->assertExists($uscita->file_caricato_path);
    Queue::assertNothingPushed();
});

test('deduplica: non si aggiunge una URL già presente nella rassegna', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->create(['url' => 'https://x.it/a']);

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->call('nuovaUscita')
        ->set('tipo_media', TipoMedia::Online->value)
        ->set('titolo', 'Doppione')
        ->set('testata_nome', 'Testata X')
        ->set('data_pubblicazione', '2026-06-25')
        ->set('url', 'https://x.it/a')
        ->call('salvaUscita')
        ->assertHasErrors('url');

    expect(Uscita::where('url', 'https://x.it/a')->count())->toBe(1);
});

test('ricattura di un\'uscita in errore: ripulisce l\'errore e riaccoda', function () {
    Queue::fake();
    $rassegna = Rassegna::factory()->create();
    $uscita = Uscita::factory()->for($rassegna)->confermato()->create([
        'tipo_media' => TipoMedia::Online,
        'url' => 'https://x.it/lenta',
        'stato_cattura' => StatoCattura::Errore,
        'errore_cattura' => 'Timeout',
    ]);

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->call('avviaCattura', $uscita->id);

    $uscita->refresh();
    expect($uscita->stato_cattura)->toBe(StatoCattura::InAttesa)
        ->and($uscita->errore_cattura)->toBeNull();

    Queue::assertPushed(CatturaUscita::class);
});

test('sostituzione manuale del file: salva il file e porta a catturato', function () {
    Storage::fake('public');
    $rassegna = Rassegna::factory()->create();
    $uscita = Uscita::factory()->for($rassegna)->confermato()->create([
        'tipo_media' => TipoMedia::Online,
        'url' => 'https://x.it/paywall',
        'stato_cattura' => StatoCattura::Errore,
        'errore_cattura' => 'Articolo troncato dal paywall',
    ]);

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->set('uscitaFileId', $uscita->id)
        ->set('fileSostitutivo', UploadedFile::fake()->image('sostituto.png'))
        ->call('salvaFileSostitutivo')
        ->assertHasNoErrors();

    $uscita->refresh();
    expect($uscita->stato)->toBe(StatoUscita::Catturato)
        ->and($uscita->errore_cattura)->toBeNull()
        ->and($uscita->file_caricato_path)->not->toBeNull();

    Storage::disk('public')->assertExists($uscita->file_caricato_path);
});

test('scarto di un\'uscita: passa a scartato ma resta (soft delete non usato qui)', function () {
    $rassegna = Rassegna::factory()->create();
    $uscita = Uscita::factory()->for($rassegna)->confermato()->create();

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->call('scarta', $uscita->id);

    expect($uscita->fresh()->stato)->toBe(StatoUscita::Scartato);
});
