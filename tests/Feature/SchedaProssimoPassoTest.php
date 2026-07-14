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

test('le metriche sono cliccabili e portano alle rispettive schermate', function () {
    $rassegna = Rassegna::factory()->create();

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('href="'.route('rassegne.candidati', $rassegna).'"')
        ->assertSeeHtml('href="'.route('rassegne.revisione', $rassegna).'"')
        ->assertSeeHtml('href="'.route('rassegne.pdf', $rassegna).'"')
        ->assertSeeHtml('href="'.route('rassegne.uscite', $rassegna).'"');
});

test('sotto le metriche c\'è un solo pulsante per generare il PDF', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->approvato()->create();

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSee('Ordina e genera il PDF')
        // I vecchi bottoni contestuali non ci sono più: la navigazione è nelle metriche.
        ->assertDontSee('Conferma i')
        ->assertDontSee('Revisiona le');
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
