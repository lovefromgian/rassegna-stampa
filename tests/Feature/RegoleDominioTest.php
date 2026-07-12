<?php

use App\Models\Cliente;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\Testata;
use App\Models\Uscita;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Database\UniqueConstraintViolationException;

/*
 * Regole di business codificate nello schema/modello (docs/regole-business.md).
 */

test('la stessa URL non si ripropone nella stessa rassegna (deduplica §3)', function () {
    $rassegna = Rassegna::factory()->create();
    $testata = Testata::factory()->create();

    Uscita::factory()->for($rassegna)->for($testata)->create(['url' => 'https://ilgoriziano.it/grado']);

    expect(fn () => Uscita::factory()->for($rassegna)->for($testata)->create(['url' => 'https://ilgoriziano.it/grado']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('la stessa URL è ammessa in rassegne diverse', function () {
    $r1 = Rassegna::factory()->create();
    $r2 = Rassegna::factory()->create();
    $testata = Testata::factory()->create();

    Uscita::factory()->for($r1)->for($testata)->create(['url' => 'https://x.it/a']);
    Uscita::factory()->for($r2)->for($testata)->create(['url' => 'https://x.it/a']);

    expect(Uscita::where('url', 'https://x.it/a')->count())->toBe(2);
});

test('il log di audit è immutabile: non si modifica (§11)', function () {
    $log = LogAzione::create(['user_id' => User::factory()->create()->id, 'azione' => 'test', 'entita_tipo' => 'X', 'entita_id' => 1]);

    expect(fn () => $log->update(['azione' => 'manomesso']))->toThrow(RuntimeException::class);
});

test('il log di audit è immutabile: non si cancella (§11)', function () {
    $log = LogAzione::create(['user_id' => User::factory()->create()->id, 'azione' => 'test', 'entita_tipo' => 'X', 'entita_id' => 1]);

    expect(fn () => $log->delete())->toThrow(RuntimeException::class);
});

test('il service Audit registra chi e cosa', function () {
    $utente = User::factory()->create();
    $this->actingAs($utente);
    $cliente = Cliente::factory()->create();

    $log = Audit::registra('modifica_cliente', $cliente, ['campo' => 'nome']);

    expect($log->user_id)->toBe($utente->id)
        ->and($log->azione)->toBe('modifica_cliente')
        ->and($log->entita_tipo)->toBe(Cliente::class)
        ->and($log->entita_id)->toBe($cliente->id)
        ->and($log->dettagli)->toBe(['campo' => 'nome']);
});

test('un\'uscita approvata online ha materiale valido con lo screenshot', function () {
    $uscita = Uscita::factory()->approvato()->make();
    expect($uscita->haMaterialeValido())->toBeTrue();
});

test('un\'uscita senza screenshot né file caricato non ha materiale valido', function () {
    $uscita = Uscita::factory()->make(['screenshot_path' => null, 'file_caricato_path' => null]);
    expect($uscita->haMaterialeValido())->toBeFalse();
});
