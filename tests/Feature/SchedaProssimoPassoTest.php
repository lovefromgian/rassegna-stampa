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

test('la scheda usa lo stepper a 5 voci come navigazione (niente quadrati/pulsanti sotto)', function () {
    $rassegna = Rassegna::factory()->create();

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSeeHtml('data-fase="candidati"')
        ->assertSeeHtml('data-fase="revisione"')
        ->assertSeeHtml('data-fase="approvate"')
        ->assertSeeHtml('data-fase="pdf"')
        ->assertSeeHtml('data-fase="scartate"')
        // I vecchi quadrati e il pulsante contestuale non ci sono più.
        ->assertDontSee('Candidati da decidere')
        ->assertDontSee('Ordina e genera il PDF');
});

test('la nota mostra il motivo reale di blocco del PDF', function () {
    $rassegna = Rassegna::factory()->create();
    Uscita::factory()->count(4)->for($rassegna)->create(['stato' => StatoUscita::Candidato]);

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->assertSee('4 uscite ancora da confermare o scartare');
});
