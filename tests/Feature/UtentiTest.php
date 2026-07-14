<?php

use App\Enums\RuoloUtente;
use App\Livewire\Utenti;
use App\Models\LogAzione;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

// ---- Accesso ----

test('l\'operatore non può accedere alla gestione utenti', function () {
    $this->actingAs(User::factory()->operatore()->create())
        ->get(route('utenti.index'))
        ->assertForbidden();
});

test('il supervisore vede l\'elenco utenti', function () {
    $sup = User::factory()->supervisore()->create(['name' => 'Marco Super']);
    User::factory()->operatore()->create(['name' => 'Lucia Oper']);

    $this->actingAs($sup)->get(route('utenti.index'))
        ->assertOk()
        ->assertSee('Marco Super')
        ->assertSee('Lucia Oper');
});

// ---- Creazione / modifica ----

test('il supervisore crea un utente operatore', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());

    Livewire::test(Utenti\Modifica::class)
        ->set('name', 'Nuovo Operatore')
        ->set('email', 'nuovo@example.com')
        ->set('ruolo', RuoloUtente::Operatore->value)
        ->set('password', 'segretissima')
        ->call('salva')
        ->assertHasNoErrors()
        ->assertRedirect(route('utenti.index'));

    $utente = User::where('email', 'nuovo@example.com')->firstOrFail();
    expect($utente->ruolo)->toBe(RuoloUtente::Operatore)
        ->and($utente->attivo)->toBeTrue()
        ->and(Hash::check('segretissima', $utente->password))->toBeTrue();
    expect(LogAzione::where('azione', 'crea_utente')->exists())->toBeTrue();
});

test('la creazione richiede la password', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());

    Livewire::test(Utenti\Modifica::class)
        ->set('name', 'Senza Password')
        ->set('email', 'sp@example.com')
        ->call('salva')
        ->assertHasErrors('password');
});

test('in modifica la password si cambia solo se compilata', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $utente = User::factory()->create(['password' => Hash::make('vecchia123')]);

    // Senza password → resta la vecchia.
    Livewire::test(Utenti\Modifica::class, ['utente' => $utente])
        ->set('name', 'Nome Nuovo')
        ->call('salva')
        ->assertHasNoErrors();
    expect(Hash::check('vecchia123', $utente->fresh()->password))->toBeTrue();

    // Con password → cambia.
    Livewire::test(Utenti\Modifica::class, ['utente' => $utente])
        ->set('password', 'nuovapass123')
        ->call('salva');
    expect(Hash::check('nuovapass123', $utente->fresh()->password))->toBeTrue();
});

test('l\'email deve essere unica', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    User::factory()->create(['email' => 'preso@example.com']);

    Livewire::test(Utenti\Modifica::class)
        ->set('name', 'Tizio')
        ->set('email', 'preso@example.com')
        ->set('password', 'password12')
        ->call('salva')
        ->assertHasErrors('email');
});

// ---- Attivazione ----

test('il supervisore disattiva e riattiva un utente, con audit', function () {
    Livewire::actingAs(User::factory()->supervisore()->create());
    $utente = User::factory()->create(['attivo' => true]);

    Livewire::test(Utenti\Elenco::class)->call('cambiaAttivazione', $utente->id);
    expect($utente->fresh()->attivo)->toBeFalse();
    expect(LogAzione::where('azione', 'disattiva_utente')->exists())->toBeTrue();

    Livewire::test(Utenti\Elenco::class)->call('cambiaAttivazione', $utente->id);
    expect($utente->fresh()->attivo)->toBeTrue();
});

test('il supervisore non può disattivare sé stesso', function () {
    $sup = User::factory()->supervisore()->create();
    Livewire::actingAs($sup);

    Livewire::test(Utenti\Elenco::class)
        ->call('cambiaAttivazione', $sup->id)
        ->assertForbidden();

    expect($sup->fresh()->attivo)->toBeTrue();
});

// ---- Login bloccato ----

test('un utente disattivato non può accedere', function () {
    User::factory()->create(['email' => 'off@example.com', 'password' => bcrypt('segreta12'), 'attivo' => false]);

    $this->post('/login', ['email' => 'off@example.com', 'password' => 'segreta12'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
