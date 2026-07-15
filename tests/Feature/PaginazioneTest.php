<?php

use App\Models\Cliente;
use App\Models\User;

// La paginazione deve usare la vista custom del progetto (.pagination-link), non la
// default Tailwind, le cui frecce SVG (senza le utility w-5/h-5) diventavano enormi.

test('la paginazione usa la vista del progetto, senza SVG', function () {
    Cliente::factory()->count(20)->create();

    $html = $this->actingAs(User::factory()->supervisore()->create())
        ->get(route('clienti.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('pagination-link')  // vista custom attiva
        ->and($html)->not->toContain('<svg');    // niente icone SVG che esplodono
});
