<?php

use App\Livewire\Clienti;
use App\Livewire\Rassegne;
use App\Models\Cliente;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\User;
use Livewire\Livewire;

// ---- Cliente ----

test('il supervisore elimina un cliente (soft delete) e resta l\'audit', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $cliente = Cliente::factory()->create();

    Livewire::test(Clienti\Scheda::class, ['cliente' => $cliente])
        ->call('elimina')
        ->assertRedirect(route('clienti.index'));

    $this->assertSoftDeleted('clienti', ['id' => $cliente->id]);
    expect(LogAzione::where('azione', 'elimina_cliente')->where('entita_id', $cliente->id)->exists())->toBeTrue();
});

test('l\'operatore non può eliminare un cliente', function () {
    Livewire::actingAs(User::factory()->operatore()->create());
    $cliente = Cliente::factory()->create();

    Livewire::test(Clienti\Scheda::class, ['cliente' => $cliente])
        ->call('elimina')
        ->assertForbidden();

    $this->assertDatabaseHas('clienti', ['id' => $cliente->id, 'deleted_at' => null]);
});

test('il bottone Elimina cliente c\'è per il supervisore, non per l\'operatore', function () {
    $cliente = Cliente::factory()->create();

    Livewire::actingAs(User::factory()->supervisore()->create());
    Livewire::test(Clienti\Scheda::class, ['cliente' => $cliente])->assertSee('Elimina');

    Livewire::actingAs(User::factory()->operatore()->create());
    Livewire::test(Clienti\Scheda::class, ['cliente' => $cliente])->assertDontSee('Elimina');
});

// ---- Rassegna ----

test('il supervisore elimina una rassegna (soft delete) e resta l\'audit', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $rassegna = Rassegna::factory()->create();

    Livewire::test(Rassegne\Scheda::class, ['rassegna' => $rassegna])
        ->call('elimina')
        ->assertRedirect(route('clienti.show', $rassegna->cliente_id));

    $this->assertSoftDeleted('rassegne', ['id' => $rassegna->id]);
    expect(LogAzione::where('azione', 'elimina_rassegna')->where('entita_id', $rassegna->id)->exists())->toBeTrue();
});

test('l\'operatore non può eliminare una rassegna', function () {
    Livewire::actingAs(User::factory()->operatore()->create());
    $rassegna = Rassegna::factory()->create();

    Livewire::test(Rassegne\Scheda::class, ['rassegna' => $rassegna])
        ->call('elimina')
        ->assertForbidden();

    $this->assertDatabaseHas('rassegne', ['id' => $rassegna->id, 'deleted_at' => null]);
});

test('il bottone Elimina rassegna c\'è per il supervisore, non per l\'operatore', function () {
    $rassegna = Rassegna::factory()->create();

    Livewire::actingAs(User::factory()->supervisore()->create());
    Livewire::test(Rassegne\Scheda::class, ['rassegna' => $rassegna])->assertSee('Elimina');

    Livewire::actingAs(User::factory()->operatore()->create());
    Livewire::test(Rassegne\Scheda::class, ['rassegna' => $rassegna])->assertDontSee('Elimina');
});
