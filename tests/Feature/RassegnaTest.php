<?php

use App\Enums\StatoRassegna;
use App\Livewire\Rassegne\Modifica;
use App\Models\Cliente;
use App\Models\Rassegna;
use App\Models\User;
use Livewire\Livewire;

test('l\'operatore può creare una rassegna', function () {
    $cliente = Cliente::factory()->create();

    Livewire::actingAs(User::factory()->operatore()->create());

    Livewire::test(Modifica::class)
        ->set('cliente_id', $cliente->id)
        ->set('titolo', 'Grado punta sulla cultura')
        ->set('parole_chiave', "Grado\nmusei")
        ->set('monitoraggio_inizio', '2026-06-25')
        ->set('monitoraggio_fine', '2026-07-09')
        ->call('salva')
        ->assertHasNoErrors()
        ->assertRedirect();

    $rassegna = Rassegna::where('titolo', 'Grado punta sulla cultura')->firstOrFail();
    expect($rassegna->parole_chiave)->toBe(['Grado', 'musei'])
        ->and($rassegna->stato)->toBe(StatoRassegna::InRaccolta);
});

test('la data del comunicato precompila il periodo di monitoraggio (+14 giorni)', function () {
    Livewire::actingAs(User::factory()->operatore()->create());

    Livewire::test(Modifica::class)
        ->set('comunicato_data', '2026-06-25')
        ->assertSet('monitoraggio_inizio', '2026-06-25')
        ->assertSet('monitoraggio_fine', '2026-07-09');
});

test('senza parole chiave la rassegna non si salva', function () {
    $cliente = Cliente::factory()->create();

    Livewire::actingAs(User::factory()->operatore()->create());

    Livewire::test(Modifica::class)
        ->set('cliente_id', $cliente->id)
        ->set('titolo', 'Senza chiavi')
        ->set('parole_chiave', '')
        ->set('monitoraggio_inizio', '2026-06-25')
        ->set('monitoraggio_fine', '2026-07-09')
        ->call('salva')
        ->assertHasErrors('parole_chiave');
});

test('l\'eliminazione della rassegna è solo del supervisore', function () {
    $rassegna = Rassegna::factory()->create();

    expect(User::factory()->operatore()->create()->can('delete', $rassegna))->toBeFalse()
        ->and(User::factory()->supervisore()->create()->can('delete', $rassegna))->toBeTrue();
});

test('la riapertura di una rassegna chiusa è solo del supervisore', function () {
    $rassegna = Rassegna::factory()->chiusa()->create();

    expect(User::factory()->operatore()->create()->can('riapri', $rassegna))->toBeFalse()
        ->and(User::factory()->supervisore()->create()->can('riapri', $rassegna))->toBeTrue();
});
