<?php

use App\Enums\StatoCattura;
use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Jobs\CatturaUscita;
use App\Livewire\Uscite\Gestore;
use App\Models\LogAzione;
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

test('la gestione uscite vive nella schermata dedicata, non nella scheda', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->catturato()->create();

    $utente = User::factory()->operatore()->create();

    // La scheda mostra il riepilogo compatto e SOLO il link "Aggiungi a mano",
    // senza le azioni di gestione (aggiunta form, ricattura, sostituzione, scarto).
    $scheda = $this->actingAs($utente)->get(route('rassegne.show', $rassegna));
    $scheda->assertOk()
        ->assertSee('Uscite raccolte')
        ->assertSee('Aggiungi a mano')
        ->assertDontSee('Ricattura')
        ->assertDontSee('Sostituisci file')
        ->assertDontSee('>Scarta<', false)
        ->assertDontSee('+ Aggiungi uscita');

    // La schermata dedicata monta il gestore con le sue azioni.
    $this->actingAs($utente)->get(route('rassegne.uscite', $rassegna))
        ->assertOk()
        ->assertSee('Gestione uscite')
        ->assertSee('+ Aggiungi uscita');
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

test('il supervisore elimina un\'uscita dal gestore (soft delete → cestino)', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $rassegna = Rassegna::factory()->create();
    $uscita = Uscita::factory()->for($rassegna)->scartato()->create();

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->call('elimina', $uscita->id);

    $this->assertSoftDeleted('uscite', ['id' => $uscita->id]);
    expect(LogAzione::where('azione', 'elimina_uscita')->exists())->toBeTrue();
});

test('eliminazione in blocco delle uscite selezionate (soft delete)', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $rassegna = Rassegna::factory()->create();
    $a = Uscita::factory()->for($rassegna)->scartato()->create();
    $b = Uscita::factory()->for($rassegna)->scartato()->create();
    $c = Uscita::factory()->for($rassegna)->approvato()->create();

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->set('selezionati', [$a->id, $b->id])
        ->call('eliminaSelezionati');

    $this->assertSoftDeleted('uscite', ['id' => $a->id]);
    $this->assertSoftDeleted('uscite', ['id' => $b->id]);
    $this->assertNotSoftDeleted('uscite', ['id' => $c->id]);
});

test('l\'operatore non ha i controlli di eliminazione nel gestore', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->scartato()->create();

    Livewire::test(Gestore::class, ['rassegna' => $rassegna]) // operatore (beforeEach)
        ->assertDontSee('Elimina selezionate')
        ->assertDontSee('Seleziona tutte');
});

test('il gestore mostra la miniatura del materiale, e un segnaposto se il file manca', function () {
    Storage::fake('public');
    $rassegna = Rassegna::factory()->create();
    // Con file esistente → miniatura.
    Storage::disk('public')->put('catture/screenshot/ok.png', 'png');
    Uscita::factory()->for($rassegna)->approvato()->create(['screenshot_path' => 'catture/screenshot/ok.png']);
    // Con path ma file mancante → segnaposto.
    Uscita::factory()->for($rassegna)->approvato()->create(['screenshot_path' => 'catture/screenshot/perso.png']);

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('alt="Anteprima"')
        ->assertSee('Anteprima non disponibile');
});

test('il gestore filtra le uscite per stato (es. solo scartate)', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->scartato()->create(['titolo' => 'Articolo Scartato']);
    Uscita::factory()->for($rassegna)->catturato()->create(['titolo' => 'Articolo Catturato']);

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->set('filtroStato', StatoUscita::Scartato->value)
        ->assertSee('Articolo Scartato')
        ->assertDontSee('Articolo Catturato');
});

test('scarto di un\'uscita: passa a scartato ma resta (soft delete non usato qui)', function () {
    $rassegna = Rassegna::factory()->create();
    $uscita = Uscita::factory()->for($rassegna)->confermato()->create();

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->call('scarta', $uscita->id);

    expect($uscita->fresh()->stato)->toBe(StatoUscita::Scartato);
});
