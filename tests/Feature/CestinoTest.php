<?php

use App\Livewire\Cestino;
use App\Models\Cliente;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\User;
use Livewire\Livewire;

test('il cestino elenca i record eliminati', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $cliente = Cliente::factory()->create(['nome' => 'Cliente Cestinato']);
    $cliente->delete();
    $rassegna = Rassegna::factory()->create(['titolo' => 'Rassegna Cestinata']);
    $rassegna->delete();

    Livewire::test(Cestino::class)
        ->assertSee('Cliente Cestinato')
        ->assertSee('Rassegna Cestinata');
});

test('il supervisore ripristina un cliente dal cestino', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $cliente = Cliente::factory()->create();
    $cliente->delete();

    Livewire::test(Cestino::class)->call('ripristina', 'cliente', $cliente->id);

    $this->assertDatabaseHas('clienti', ['id' => $cliente->id, 'deleted_at' => null]);
    expect(LogAzione::where('azione', 'ripristina_cliente')->where('entita_id', $cliente->id)->exists())->toBeTrue();
});

test('il supervisore ripristina una rassegna dal cestino', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $rassegna = Rassegna::factory()->create();
    $rassegna->delete();

    Livewire::test(Cestino::class)->call('ripristina', 'rassegna', $rassegna->id);

    $this->assertDatabaseHas('rassegne', ['id' => $rassegna->id, 'deleted_at' => null]);
    expect(LogAzione::where('azione', 'ripristina_rassegna')->exists())->toBeTrue();
});

test('l\'operatore non può accedere al cestino', function () {
    $this->actingAs(User::factory()->operatore()->create())
        ->get(route('cestino.index'))
        ->assertForbidden();
});

test('il cestino è raggiungibile dal supervisore', function () {
    $this->actingAs(User::factory()->supervisore()->create())
        ->get(route('cestino.index'))
        ->assertOk()
        ->assertSee('Cestino');
});

test('il cestino non offre la cancellazione definitiva (spec §6/§10)', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());

    Livewire::test(Cestino::class)
        ->assertDontSee('Elimina definitivamente')
        ->assertSee('non è prevista');
});
