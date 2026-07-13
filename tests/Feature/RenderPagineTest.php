<?php

use App\Models\Cliente;
use App\Models\Rassegna;
use App\Models\User;

/*
 * Smoke test di rendering delle pagine Livewire full-page: verificano che il CORPO del
 * componente finisca davvero nel layout (regressione del bug layout slot vs @yield).
 */

beforeEach(function () {
    $this->actingAs(User::factory()->supervisore()->create());
});

test('la pagina Clienti renderizza il proprio contenuto', function () {
    $this->get(route('clienti.index'))
        ->assertOk()
        ->assertSee('Ogni cliente raccoglie')
        ->assertSee('+ Nuovo cliente');
});

test('la pagina Rassegne renderizza il proprio contenuto', function () {
    $this->get(route('rassegne.index'))
        ->assertOk()
        ->assertSee('Ogni rassegna monitora');
});

test('la scheda cliente renderizza nome e sezioni', function () {
    $cliente = Cliente::factory()->create(['nome' => 'Comune di Grado']);

    $this->get(route('clienti.show', $cliente))
        ->assertOk()
        ->assertSee('Comune di Grado')
        ->assertSee('Rassegne');
});

test('la scheda rassegna renderizza parole chiave e gestore uscite', function () {
    $rassegna = Rassegna::factory()->create(['titolo' => 'Grado punta sulla cultura']);

    $this->get(route('rassegne.show', $rassegna))
        ->assertOk()
        ->assertSee('Grado punta sulla cultura')
        ->assertSee('Parole chiave')
        ->assertSee('Uscite raccolte');
});
