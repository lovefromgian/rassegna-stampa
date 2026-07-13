<?php

use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Jobs\CatturaUscita;
use App\Livewire\Rassegne\Candidati;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Models\User;
use App\Support\Discovery\ArticleDiscoverySource;
use App\Support\Discovery\ArticoloTrovato;
use App\Support\Discovery\FakeDiscoverySource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::actingAs(User::factory()->operatore()->create());
});

test('confermare i candidati selezionati li porta a confermato e accoda la cattura', function () {
    Queue::fake();
    $rassegna = Rassegna::factory()->create();
    $c1 = Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato, 'tipo_media' => TipoMedia::Online, 'url' => 'https://x.it/1']);
    $c2 = Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato, 'tipo_media' => TipoMedia::Online, 'url' => 'https://x.it/2']);

    Livewire::test(Candidati::class, ['rassegna' => $rassegna])
        ->set('selezionati', [$c1->id])
        ->call('confermaSelezionati');

    expect($c1->fresh()->stato)->toBe(StatoUscita::Confermato)
        ->and($c2->fresh()->stato)->toBe(StatoUscita::Candidato); // non selezionato

    Queue::assertPushed(CatturaUscita::class, 1);
    expect(LogAzione::where('azione', 'conferma_candidato')->where('entita_id', $c1->id)->exists())->toBeTrue();
});

test('scartare i candidati selezionati li porta a scartato', function () {
    $rassegna = Rassegna::factory()->create();
    $c = Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato]);

    Livewire::test(Candidati::class, ['rassegna' => $rassegna])
        ->set('selezionati', [$c->id])
        ->call('scartaSelezionati');

    expect($c->fresh()->stato)->toBe(StatoUscita::Scartato);
    expect(LogAzione::where('azione', 'scarto_uscita')->exists())->toBeTrue();
});

test('seleziona tutti raccoglie tutti i candidati', function () {
    $rassegna = Rassegna::factory()->create();
    $ids = collect([1, 2, 3])->map(fn () => Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato])->id)->all();
    Uscita::factory()->for($rassegna)->confermato()->create(); // non candidato

    Livewire::test(Candidati::class, ['rassegna' => $rassegna])
        ->call('selezionaTutti', true)
        ->assertCount('selezionati', 3);
});

test('scansiona ora crea nuovi candidati dalla fonte di scoperta', function () {
    $rassegna = Rassegna::factory()->create([
        'parole_chiave' => ['Grado'],
        'parole_escluse' => [],
        'monitoraggio_inizio' => '2026-06-25',
        'monitoraggio_fine' => '2026-07-09',
    ]);
    app()->instance(ArticleDiscoverySource::class, new FakeDiscoverySource([
        new ArticoloTrovato('Grado punta sulla cultura', 'https://n.gl/a', 'Il Goriziano', Carbon::parse('2026-06-27'), 'estratto'),
    ]));

    Livewire::test(Candidati::class, ['rassegna' => $rassegna])
        ->call('scansionaOra')
        ->assertSee('Grado punta sulla cultura'); // il nuovo candidato compare in lista

    expect(Uscita::where('url', 'https://n.gl/a')->where('stato', StatoUscita::Candidato)->exists())->toBeTrue();
});

test('i candidati sono ordinati per punteggio decrescente', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato, 'titolo' => 'Debole assai', 'punteggio_corrispondenza' => 10]);
    Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato, 'titolo' => 'Forte assai', 'punteggio_corrispondenza' => 95]);

    Livewire::test(Candidati::class, ['rassegna' => $rassegna])
        ->assertSeeInOrder(['Forte assai', 'Debole assai']);
});

test('la pagina candidati renderizza come full-page', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato, 'punteggio_corrispondenza' => 20]);

    $this->get(route('rassegne.candidati', $rassegna))
        ->assertOk()
        ->assertSee('Candidati da confermare')
        ->assertSee('Corrispondenza debole');
});
