<?php

use App\Enums\Rilevanza;
use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Jobs\GeneraPdf;
use App\Livewire\Rassegne\OrdinePdf;
use App\Models\Cliente;
use App\Models\DocumentoGenerato;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Models\User;
use App\Services\BlocchiGenerazione;
use App\Services\GeneratorePdf;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** PNG 1x1 valido salvato sul disco fake per esercitare l'incorporamento immagini. */
function screenshotFinto(string $path): void
{
    Storage::disk('public')->put($path, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    ));
}

// ---- Blocchi §7 ----

test('la generazione è bloccata se restano candidati', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato]);
    Uscita::factory()->for($rassegna)->approvato()->create();

    $motivi = app(BlocchiGenerazione::class)->motivi($rassegna);
    expect($motivi)->not->toBeEmpty()
        ->and(implode(' ', $motivi))->toContain('candidato');
});

test('la generazione è bloccata se un\'approvata non ha materiale', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->approvato()->create([
        'screenshot_path' => null, 'file_caricato_path' => null,
    ]);

    expect(app(BlocchiGenerazione::class)->puoGenerare($rassegna))->toBeFalse();
});

test('la generazione è possibile con approvate complete e nessun candidato', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->approvato()->create();
    Uscita::factory()->for($rassegna)->scartato()->create();

    expect(app(BlocchiGenerazione::class)->puoGenerare($rassegna))->toBeTrue();
});

// ---- Ordinamento ----

test('l\'ordine proposto è rilevanza poi data', function () {
    $rassegna = Rassegna::factory()->create();
    $cit = Uscita::factory()->for($rassegna)->approvato()->create(['rilevanza' => Rilevanza::Citazione, 'data_pubblicazione' => '2026-06-25']);
    $princVecchia = Uscita::factory()->for($rassegna)->approvato()->create(['rilevanza' => Rilevanza::Principale, 'data_pubblicazione' => '2026-06-20']);
    $princNuova = Uscita::factory()->for($rassegna)->approvato()->create(['rilevanza' => Rilevanza::Principale, 'data_pubblicazione' => '2026-06-27']);

    $ordine = app(GeneratorePdf::class)->usciteOrdinate($rassegna)->pluck('id')->all();

    expect($ordine)->toBe([$princVecchia->id, $princNuova->id, $cit->id]);
});

test('il riordino manuale prevale sulla proposta', function () {
    Livewire::actingAs(User::factory()->operatore()->create());
    $rassegna = Rassegna::factory()->create();
    $a = Uscita::factory()->for($rassegna)->approvato()->create(['rilevanza' => Rilevanza::Principale, 'data_pubblicazione' => '2026-06-20']);
    $b = Uscita::factory()->for($rassegna)->approvato()->create(['rilevanza' => Rilevanza::Principale, 'data_pubblicazione' => '2026-06-21']);

    Livewire::test(OrdinePdf::class, ['rassegna' => $rassegna])
        ->call('spostaGiu', $a->id); // a scende sotto b

    $ordine = app(GeneratorePdf::class)->usciteOrdinate($rassegna)->pluck('id')->all();
    expect($ordine)->toBe([$b->id, $a->id]);
});

// ---- Generazione reale (dompdf) ----

test('genera un PDF versionato con snapshot delle uscite e file su disco', function () {
    Storage::fake('public');
    $cliente = Cliente::factory()->create(['colore_accento' => '#E8836B']);
    $rassegna = Rassegna::factory()->for($cliente)->create();
    screenshotFinto('catture/screenshot/reale.png');
    $u1 = Uscita::factory()->for($rassegna)->approvato()->create([
        'rilevanza' => Rilevanza::Principale, 'tipo_media' => TipoMedia::Online,
        'screenshot_path' => 'catture/screenshot/reale.png',
    ]);
    $u2 = Uscita::factory()->for($rassegna)->approvato()->create(['rilevanza' => Rilevanza::Secondaria]);
    $autore = User::factory()->create();

    $doc = app(GeneratorePdf::class)->genera($rassegna, $autore);

    expect($doc->versione)->toBe(1)
        ->and($doc->uscite_incluse)->toBe([$u1->id, $u2->id])
        ->and($doc->generato_da)->toBe($autore->id);
    Storage::disk('public')->assertExists($doc->file_path);
    // Il contenuto è un vero PDF.
    expect(Storage::disk('public')->get($doc->file_path))->toStartWith('%PDF');

    // Seconda generazione → v2, la v1 resta.
    $doc2 = app(GeneratorePdf::class)->genera($rassegna, $autore);
    expect($doc2->versione)->toBe(2)
        ->and(DocumentoGenerato::count())->toBe(2);
});

test('il generatore rifiuta se i blocchi non sono soddisfatti', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato]);

    expect(fn () => app(GeneratorePdf::class)->genera($rassegna, User::factory()->create()))
        ->toThrow(RuntimeException::class);
});

// ---- Job e UI ----

test('il pulsante genera accoda il job quando è possibile', function () {
    Queue::fake();
    Livewire::actingAs(User::factory()->operatore()->create());
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->approvato()->create();

    Livewire::test(OrdinePdf::class, ['rassegna' => $rassegna])->call('genera');

    Queue::assertPushed(GeneraPdf::class);
});

test('il pulsante genera non accoda nulla se bloccato e mostra il motivo', function () {
    Queue::fake();
    Livewire::actingAs(User::factory()->operatore()->create());
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato]);

    Livewire::test(OrdinePdf::class, ['rassegna' => $rassegna])
        ->call('genera')
        ->assertSee('Genera PDF'); // resta sulla pagina

    Queue::assertNothingPushed();
});

test('il job GeneraPdf produce il documento e registra l\'audit con l\'autore', function () {
    Storage::fake('public');
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->approvato()->create();
    $autore = User::factory()->supervisore()->create();

    GeneraPdf::dispatchSync($rassegna, $autore);

    $doc = DocumentoGenerato::firstOrFail();
    expect($doc->versione)->toBe(1);
    expect(LogAzione::where('azione', 'genera_pdf')->where('user_id', $autore->id)->exists())->toBeTrue();
});

// ---- Download ----

test('il download restituisce il PDF, segna scaricato_il e registra l\'audit', function () {
    Storage::fake('public');
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->approvato()->create();
    $autore = User::factory()->create();
    $doc = app(GeneratorePdf::class)->genera($rassegna, $autore);

    $this->actingAs(User::factory()->operatore()->create())
        ->get(route('documenti.download', $doc))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($doc->fresh()->scaricato_il)->not->toBeNull();
    expect(LogAzione::where('azione', 'scarica_pdf')->exists())->toBeTrue();
});

// ---- Eliminazione uscite dall'ordine PDF (collaudo) ----

test('il supervisore elimina un\'uscita dall\'ordine PDF (soft delete + audit)', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $rassegna = Rassegna::factory()->create();
    $u = Uscita::factory()->for($rassegna)->approvato()->create();

    Livewire::test(OrdinePdf::class, ['rassegna' => $rassegna])
        ->assertViewHas('puoEliminare', true)
        ->assertSee('🗑')
        ->call('elimina', $u->id);

    expect($u->fresh()->trashed())->toBeTrue();
    expect(LogAzione::where('azione', 'elimina_uscita')->where('entita_id', $u->id)->exists())->toBeTrue();
});

test('l\'operatore non può eliminare dall\'ordine PDF né vede il pulsante', function () {
    Livewire::actingAs(User::factory()->operatore()->create());
    $rassegna = Rassegna::factory()->create();
    $u = Uscita::factory()->for($rassegna)->approvato()->create();

    Livewire::test(OrdinePdf::class, ['rassegna' => $rassegna])
        ->assertViewHas('puoEliminare', false)
        ->assertDontSee('🗑')
        ->call('elimina', $u->id)
        ->assertForbidden();

    expect($u->fresh()->trashed())->toBeFalse();
});
