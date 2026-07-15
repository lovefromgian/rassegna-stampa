<?php

use App\Models\User;

test('un utente autenticato può aprire il manuale', function () {
    $this->actingAs(User::factory()->operatore()->create())
        ->get(route('manuale'))
        ->assertOk()
        ->assertSee("Manuale d'uso", false)
        ->assertSee('Le fasi di una rassegna');
});

test('un ospite non accede al manuale (redirect al login)', function () {
    $this->get(route('manuale'))->assertRedirect(route('login'));
});
