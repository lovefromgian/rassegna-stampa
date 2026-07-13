<?php

use App\Livewire\Archivio;
use App\Livewire\Audit\Registro;
use App\Livewire\Statistiche;
use App\Models\Cliente;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->utente = User::factory()->supervisore()->create(['name' => 'Marco B.']);
    Livewire::actingAs($this->utente);
    $this->actingAs($this->utente);
});

// ---- Log ----

test('il registro mostra le azioni e filtra per azione e utente', function () {
    $altro = User::factory()->create(['name' => 'Laura T.']);
    $uscita = Uscita::factory()->create();
    // Marcatori nei dettagli (compaiono solo nelle righe, non nei menu a tendina).
    LogAzione::create(['user_id' => $this->utente->id, 'azione' => 'genera_pdf', 'entita_tipo' => Uscita::class, 'entita_id' => $uscita->id, 'dettagli' => ['versione' => 1]]);
    LogAzione::create(['user_id' => $altro->id, 'azione' => 'approva_uscita', 'entita_tipo' => Uscita::class, 'entita_id' => $uscita->id, 'dettagli' => ['rilevanza' => 'principale']]);

    Livewire::test(Registro::class)
        ->assertSee('versione: 1')
        ->assertSee('rilevanza: principale')
        ->set('azione', 'genera_pdf')
        ->assertSee('versione: 1')
        ->assertDontSee('rilevanza: principale');
});

test('il log per rassegna mostra solo le azioni delle sue uscite', function () {
    $rassegna = Rassegna::factory()->create();
    $uscitaSua = Uscita::factory()->for($rassegna)->create();
    $uscitaAltrove = Uscita::factory()->create();
    LogAzione::create(['user_id' => $this->utente->id, 'azione' => 'approva_uscita', 'entita_tipo' => Uscita::class, 'entita_id' => $uscitaSua->id, 'dettagli' => ['rilevanza' => 'principale']]);
    LogAzione::create(['user_id' => $this->utente->id, 'azione' => 'approva_uscita', 'entita_tipo' => Uscita::class, 'entita_id' => $uscitaAltrove->id]);

    Livewire::test(Registro::class, ['rassegna' => $rassegna])
        ->assertSee('#'.$uscitaSua->id)
        ->assertDontSee('#'.$uscitaAltrove->id);
});

test('il log si apre come pagina full-page', function () {
    LogAzione::create(['user_id' => $this->utente->id, 'azione' => 'genera_pdf', 'entita_tipo' => Rassegna::class, 'entita_id' => 1]);

    $this->get(route('log.index'))->assertOk()->assertSee('Log azioni');
});

// ---- Archivio ----

test('l\'archivio cerca nel testo estratto', function () {
    $u = Uscita::factory()->catturato()->create(['testo_estratto' => 'Il relitto della nave romana Iulia Felix a Grado']);
    Uscita::factory()->catturato()->create(['testo_estratto' => 'Tutt\'altro argomento senza attinenza']);

    Livewire::test(Archivio::class)
        ->set('termine', 'Iulia Felix')
        ->assertSee($u->titolo);
});

test('l\'archivio senza termine non mostra risultati', function () {
    Uscita::factory()->catturato()->create(['testo_estratto' => 'qualcosa']);

    Livewire::test(Archivio::class)
        ->assertSee('Scrivi un termine');
});

test('l\'archivio filtra per cliente', function () {
    $clienteA = Cliente::factory()->create();
    $rassegnaA = Rassegna::factory()->for($clienteA)->create();
    $trovata = Uscita::factory()->for($rassegnaA)->catturato()->create(['testo_estratto' => 'cultura a Grado museo']);
    $altra = Uscita::factory()->catturato()->create(['testo_estratto' => 'cultura a Grado museo']);

    Livewire::test(Archivio::class)
        ->set('termine', 'cultura')
        ->set('clienteId', $clienteA->id)
        ->assertSee($trovata->titolo)
        ->assertDontSee($altra->titolo);
});

// ---- Statistiche ----

test('le statistiche contano uscite per cliente e testata', function () {
    $cliente = Cliente::factory()->create(['nome' => 'Comune di Grado']);
    $rassegna = Rassegna::factory()->for($cliente)->create();
    Uscita::factory()->count(3)->for($rassegna)->create();

    Livewire::test(Statistiche::class)
        ->assertSee('Comune di Grado')
        ->assertSee('Uscite per cliente')
        ->assertSee('Testate più presenti');
});

test('le statistiche si aprono come pagina full-page', function () {
    Cliente::factory()->create();

    $this->get(route('statistiche.index'))->assertOk()->assertSee('Statistiche');
});
