<?php

use App\Models\User;

test('gli ospiti vengono rediretti al login', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/clienti')->assertRedirect('/login');
    $this->get('/rassegne')->assertRedirect('/login');
});

test('un utente registrato può autenticarsi', function () {
    $user = User::factory()->create([
        'email' => 'mario@example.com',
        'password' => bcrypt('segreta'),
    ]);

    $this->post('/login', [
        'email' => 'mario@example.com',
        'password' => 'segreta',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('credenziali errate non autenticano', function () {
    User::factory()->create(['email' => 'mario@example.com', 'password' => bcrypt('segreta')]);

    $this->post('/login', ['email' => 'mario@example.com', 'password' => 'sbagliata'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('un utente autenticato vede la dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Gestionale rassegne stampa');
});

test('il logout termina la sessione', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});
