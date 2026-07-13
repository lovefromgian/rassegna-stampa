<?php

use App\Enums\StatoUscita;
use App\Livewire\Rassegne\Scheda;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::actingAs(User::factory()->operatore()->create());
});

test('con candidati pendenti il passo primario è "Conferma"', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->count(2)->for($rassegna)->create(['stato' => StatoUscita::Candidato]);

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-passo="conferma"')
        ->assertSee('Conferma i 2 candidati proposti');
});

test('a candidati=0 con catture da revisionare il passo primario è "Revisiona"', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->count(3)->for($rassegna)->catturato()->create();

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-passo="revisiona"')
        ->assertSee('Revisiona le 3 uscite in attesa');
});

test('a tutto pronto (nessun candidato, nessuna da revisionare) il passo primario è "Genera PDF"', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->approvato()->create();

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-passo="pdf"')
        ->assertSee('Ordina e genera il PDF');
});

test('la nota mostra il motivo reale di blocco del PDF', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->count(4)->for($rassegna)->create(['stato' => StatoUscita::Candidato]);

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSee('4 uscite ancora da confermare o scartare');
});

test('le metriche mostrano i conteggi per stato (UX-02)', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->count(2)->for($rassegna)->create(['stato' => StatoUscita::Candidato]);
    Uscita::factory()->count(1)->for($rassegna)->catturato()->create();
    Uscita::factory()->count(3)->for($rassegna)->approvato()->create();
    Uscita::factory()->count(4)->for($rassegna)->scartato()->create();

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSeeInOrder([
            'Candidati da decidere', '2',
            'Da revisionare', '1',
            'Approvate', '3',
            'Scartate', '4',
        ]);
});
