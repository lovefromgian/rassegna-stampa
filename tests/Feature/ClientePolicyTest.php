<?php

use App\Livewire\Clienti\Modifica;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
 * Verifica di M1 (PROGRESS.md, punto 6): l'operatore NON riesce a modificare
 * l'anagrafica cliente nemmeno chiamando la rotta o il componente direttamente.
 * Il vincolo è lato server (Policy), non solo nascosto nella UI.
 */

test('la rotta di creazione cliente è vietata all\'operatore (403)', function () {
    $this->actingAs(User::factory()->operatore()->create())
        ->get(route('clienti.create'))
        ->assertForbidden();
});

test('la rotta di modifica cliente è vietata all\'operatore (403)', function () {
    $cliente = Cliente::factory()->create();

    $this->actingAs(User::factory()->operatore()->create())
        ->get(route('clienti.edit', $cliente))
        ->assertForbidden();
});

test('l\'operatore non può nemmeno aprire il form di creazione cliente (componente vietato)', function () {
    Livewire::actingAs(User::factory()->operatore()->create());

    Livewire::test(Modifica::class)->assertForbidden();

    expect(Cliente::count())->toBe(0);
});

test('l\'operatore non può modificare un cliente esistente dal componente', function () {
    $cliente = Cliente::factory()->create(['nome' => 'Nome Originale']);

    Livewire::actingAs(User::factory()->operatore()->create());

    Livewire::test(Modifica::class, ['cliente' => $cliente])
        ->assertForbidden();

    expect($cliente->fresh()->nome)->toBe('Nome Originale');
});

test('il supervisore può creare un cliente', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());

    Livewire::test(Modifica::class)
        ->set('nome', 'Comune di Grado')
        ->set('email_referente', 'ufficio.stampa@comunegrado.it')
        ->set('destinatari_invio', "ufficio.stampa@comunegrado.it\nsindaco@comunegrado.it")
        ->set('colore_accento', '#E8836B')
        ->call('salva')
        ->assertHasNoErrors()
        ->assertRedirect();

    $cliente = Cliente::where('nome', 'Comune di Grado')->firstOrFail();
    expect($cliente->destinatari_invio)->toBe(['ufficio.stampa@comunegrado.it', 'sindaco@comunegrado.it'])
        ->and($cliente->colore_accento)->toBe('#E8836B');
});

test('il supervisore carica il logo tramite il disco Laravel (storage astratto)', function () {
    Storage::fake('public');
    Livewire::actingAs(User::factory()->supervisore()->create());

    Livewire::test(Modifica::class)
        ->set('nome', 'Comune di Grado')
        ->set('logo', UploadedFile::fake()->image('grado_logo.png'))
        ->call('salva')
        ->assertHasNoErrors();

    $cliente = Cliente::where('nome', 'Comune di Grado')->firstOrFail();
    expect($cliente->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($cliente->logo_path);
});

test('il supervisore può eliminare un cliente, l\'operatore no', function () {
    $cliente = Cliente::factory()->create();

    expect(User::factory()->operatore()->create()->can('delete', $cliente))->toBeFalse()
        ->and(User::factory()->supervisore()->create()->can('delete', $cliente))->toBeTrue();
});
