<?php

use App\Enums\Rilevanza;
use App\Enums\StatoCattura;
use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Livewire\Rassegne\Revisione;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::actingAs(User::factory()->operatore()->create());
});

test('approvare un\'uscita catturata assegna rilevanza e la porta ad approvato', function () {
    $rassegna = Rassegna::factory()->create();
    $uscita = Uscita::factory()->for($rassegna)->catturato()->create(['tipo_media' => TipoMedia::Online]);

    Livewire::test(Revisione::class, ['rassegna' => $rassegna])
        ->assertSet('correnteId', $uscita->id)
        ->set('rilevanza', Rilevanza::Secondaria->value)
        ->set('note', 'Buona copertura')
        ->call('approva');

    $uscita->refresh();
    expect($uscita->stato)->toBe(StatoUscita::Approvato)
        ->and($uscita->rilevanza)->toBe(Rilevanza::Secondaria)
        ->and($uscita->note)->toBe('Buona copertura');

    expect(LogAzione::where('azione', 'approva_uscita')->where('entita_id', $uscita->id)->exists())->toBeTrue();
});

test('la revisione avanza alla prossima uscita catturata', function () {
    $rassegna = Rassegna::factory()->create();
    $u1 = Uscita::factory()->for($rassegna)->catturato()->create(['data_rilevamento' => now()->subHour()]);
    $u2 = Uscita::factory()->for($rassegna)->catturato()->create(['data_rilevamento' => now()]);

    $test = Livewire::test(Revisione::class, ['rassegna' => $rassegna])
        ->assertSet('correnteId', $u1->id)
        ->call('approva')
        ->assertSet('correnteId', $u2->id);
});

test('scartare un\'uscita in revisione la porta a scartato e avanza', function () {
    $rassegna = Rassegna::factory()->create();
    $uscita = Uscita::factory()->for($rassegna)->catturato()->create();

    Livewire::test(Revisione::class, ['rassegna' => $rassegna])
        ->call('scarta');

    expect($uscita->fresh()->stato)->toBe(StatoUscita::Scartato);
    expect(LogAzione::where('azione', 'scarto_uscita')->exists())->toBeTrue();
});

test('si può sostituire il file direttamente dalla revisione, restando sull\'uscita', function () {
    Storage::fake('public');
    $rassegna = Rassegna::factory()->create();
    $uscita = Uscita::factory()->for($rassegna)->catturato()->create([
        'errore_cattura' => 'Paywall', 'stato_cattura' => StatoCattura::Errore,
    ]);

    Livewire::test(Revisione::class, ['rassegna' => $rassegna])
        ->assertSet('correnteId', $uscita->id)
        ->set('fileSostitutivo', UploadedFile::fake()->image('mio-screenshot.png'))
        ->call('sostituisciFile')
        ->assertHasNoErrors()
        ->assertSet('correnteId', $uscita->id); // resta sulla stessa uscita

    $uscita->refresh();
    expect($uscita->file_caricato_path)->not->toBeNull()
        ->and($uscita->errore_cattura)->toBeNull();
    Storage::disk('public')->assertExists($uscita->file_caricato_path);
});

test('senza uscite catturate la revisione è completata', function () {
    $rassegna = Rassegna::factory()->create();

    Livewire::test(Revisione::class, ['rassegna' => $rassegna])
        ->assertSet('correnteId', null)
        ->assertSee('Revisione completata');
});

test('con catture ancora in corso la revisione lo segnala (invece di "completata")', function () {
    $rassegna = Rassegna::factory()->create();
    // Confermata e in cattura: non ancora catturata, quindi niente da revisionare ADESSO.
    Uscita::factory()->for($rassegna)->confermato()->create([
        'tipo_media' => TipoMedia::Online,
        'url' => 'https://x.it/a',
        'stato_cattura' => StatoCattura::InAttesa,
    ]);

    Livewire::test(Revisione::class, ['rassegna' => $rassegna])
        ->assertSet('correnteId', null)
        ->assertSee('Cattura in corso')
        ->assertDontSee('Revisione completata');
});
