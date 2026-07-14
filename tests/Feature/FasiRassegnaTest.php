<?php

use App\Enums\StatoUscita;
use App\Livewire\Rassegne\Candidati;
use App\Livewire\Rassegne\OrdinePdf;
use App\Livewire\Rassegne\Revisione;
use App\Livewire\Rassegne\Scheda;
use App\Livewire\Uscite\Gestore;
use App\Models\DocumentoGenerato;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::actingAs(User::factory()->operatore()->create());
});

test('sulla schermata Candidati la fase Candidati è evidenziata come corrente', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato]);

    Livewire::test(Candidati::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-fase="candidati" data-stato="corrente"');
});

test('sulla schermata Revisione: Candidati completata, Revisione corrente', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->catturato()->create(); // progresso = 1

    Livewire::test(Revisione::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-fase="candidati" data-stato="completata"')
        ->assertSeeHtml('data-fase="revisione" data-stato="corrente"')
        ->assertSeeHtml('data-fase="pdf" data-stato="attesa"');
});

test('sulla schermata Ordine/PDF a lavoro finito: le fasi precedenti sono completate', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->approvato()->create(); // niente candidati/catture → progresso 2
    DocumentoGenerato::factory()->for($rassegna)->create();

    Livewire::test(OrdinePdf::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-fase="candidati" data-stato="completata"')
        ->assertSeeHtml('data-fase="revisione" data-stato="completata"')
        ->assertSeeHtml('data-fase="pdf" data-stato="corrente"');
});

test('una fase senza lavoro resta in attesa, non completata', function () {
    // Solo candidati: la Revisione non è ancora stata raggiunta.
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->create(['stato' => StatoUscita::Candidato]);

    Livewire::test(Candidati::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-fase="revisione" data-stato="attesa"')
        ->assertSeeHtml('data-fase="pdf" data-stato="attesa"');
});

test('sulla scheda la fase corrente dello stepper è quella consigliata', function () {
    // Candidati pendenti → il passo consigliato (UX-01) è Candidati.
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->count(2)->for($rassegna)->create(['stato' => StatoUscita::Candidato]);

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-fase="candidati" data-stato="corrente"');
});

test('lo stepper ha le voci Approvate e Scartate, coi loro conteggi e link filtrati', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->count(3)->for($rassegna)->approvato()->create();
    Uscita::factory()->count(2)->for($rassegna)->scartato()->create();

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-fase="approvate" data-stato="vista"')
        ->assertSeeHtml('data-fase="scartate" data-stato="vista"')
        // Le voci portano all'elenco filtrato delle uscite.
        ->assertSeeHtml('href="'.route('rassegne.uscite', ['rassegna' => $rassegna, 'stato' => 'approvato']).'"')
        ->assertSeeHtml('href="'.route('rassegne.uscite', ['rassegna' => $rassegna, 'stato' => 'scartato']).'"')
        ->assertSee('Approvate')
        ->assertSee('Scartate');
});

test('nel gestore filtrato la voce corrispondente dello stepper è evidenziata', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->for($rassegna)->scartato()->create();

    Livewire::test(Gestore::class, ['rassegna' => $rassegna])
        ->set('filtroStato', StatoUscita::Scartato->value)
        ->assertSeeHtml('data-fase="scartate" data-stato="corrente"');
});
