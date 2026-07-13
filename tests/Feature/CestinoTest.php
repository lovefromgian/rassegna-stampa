<?php

use App\Livewire\Cestino;
use App\Models\Cliente;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
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

test('il supervisore elimina definitivamente un\'uscita e i suoi file', function () {
    Storage::fake('public');
    Livewire::actingAs(User::factory()->supervisore()->create());

    Storage::disk('public')->put('catture/screenshot/x.png', 'png');
    $uscita = Uscita::factory()->create(['screenshot_path' => 'catture/screenshot/x.png']);
    $uscita->delete();

    Livewire::test(Cestino::class)->call('eliminaDefinitivo', 'uscita', $uscita->id);

    $this->assertDatabaseMissing('uscite', ['id' => $uscita->id]); // sparita per davvero
    Storage::disk('public')->assertMissing('catture/screenshot/x.png');
    expect(LogAzione::where('azione', 'elimina_definitiva_uscita')->exists())->toBeTrue();
});

test('eliminare definitivamente una rassegna rimuove a cascata le sue uscite', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $rassegna = Rassegna::factory()->create();
    $u1 = Uscita::factory()->for($rassegna)->create();
    $u2 = Uscita::factory()->for($rassegna)->scartato()->create();
    $rassegna->delete();

    Livewire::test(Cestino::class)->call('eliminaDefinitivo', 'rassegna', $rassegna->id);

    $this->assertDatabaseMissing('rassegne', ['id' => $rassegna->id]);
    $this->assertDatabaseMissing('uscite', ['id' => $u1->id]);
    $this->assertDatabaseMissing('uscite', ['id' => $u2->id]);
});

test('l\'operatore non può eliminare definitivamente', function () {
    $cliente = Cliente::factory()->create();
    $cliente->delete();

    Livewire::actingAs(User::factory()->operatore()->create());
    // L'operatore non accede nemmeno al cestino, ma verifichiamo anche il blocco lato server:
    expect(User::factory()->operatore()->create()->can('forceDelete', $cliente))->toBeFalse();
    expect(User::factory()->supervisore()->create()->can('forceDelete', $cliente))->toBeTrue();
});

test('seleziona tutti raccoglie ogni record del cestino', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    Cliente::factory()->create()->delete();
    Rassegna::factory()->create()->delete();
    Uscita::factory()->create()->delete();

    Livewire::test(Cestino::class)
        ->call('selezionaTutti', true)
        ->assertCount('selezionati', 3);
});

test('eliminazione in blocco dei selezionati (con cascata sovrapposta)', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $cliente = Cliente::factory()->create();
    $rassegna = Rassegna::factory()->for($cliente)->create();
    $uscita = Uscita::factory()->for($rassegna)->create();
    $cliente->delete();
    $rassegna->delete();
    $uscitaAltrove = Uscita::factory()->create();
    $uscitaAltrove->delete();

    // Seleziono cliente + la sua rassegna (che verrà rimossa a cascata) + un'uscita altrove.
    Livewire::test(Cestino::class)
        ->set('selezionati', ["cliente:{$cliente->id}", "rassegna:{$rassegna->id}", "uscita:{$uscitaAltrove->id}"])
        ->call('eliminaSelezionati');

    $this->assertDatabaseMissing('clienti', ['id' => $cliente->id]);
    $this->assertDatabaseMissing('rassegne', ['id' => $rassegna->id]);
    $this->assertDatabaseMissing('uscite', ['id' => $uscita->id]); // cascata
    $this->assertDatabaseMissing('uscite', ['id' => $uscitaAltrove->id]);
});

test('ripristino in blocco dei selezionati', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $c = Cliente::factory()->create();
    $c->delete();
    $r = Rassegna::factory()->create();
    $r->delete();

    Livewire::test(Cestino::class)
        ->set('selezionati', ["cliente:{$c->id}", "rassegna:{$r->id}"])
        ->call('ripristinaSelezionati');

    $this->assertDatabaseHas('clienti', ['id' => $c->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('rassegne', ['id' => $r->id, 'deleted_at' => null]);
});

test('svuota cestino elimina definitivamente tutto', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    Cliente::factory()->create()->delete();
    Rassegna::factory()->create()->delete();
    $u = Uscita::factory()->create();
    $u->delete();

    Livewire::test(Cestino::class)->call('svuotaCestino');

    expect(Cliente::onlyTrashed()->count())->toBe(0)
        ->and(Rassegna::onlyTrashed()->count())->toBe(0)
        ->and(Uscita::onlyTrashed()->count())->toBe(0);
});

test('il log di audit resta anche dopo la cancellazione definitiva', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $uscita = Uscita::factory()->create();
    $uscita->delete();

    Livewire::test(Cestino::class)->call('eliminaDefinitivo', 'uscita', $uscita->id);

    // L'entità è sparita, ma la registrazione dell'azione resta (log immutabile).
    expect(LogAzione::where('azione', 'elimina_definitiva_uscita')->where('entita_id', $uscita->id)->exists())->toBeTrue();
});
