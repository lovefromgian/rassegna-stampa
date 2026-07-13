<?php

use App\Enums\StatoRassegna;
use App\Livewire\Rassegne\Scheda;
use App\Models\DocumentoGenerato;
use App\Models\LogAzione;
use App\Models\Rassegna;
use App\Models\User;
use Livewire\Livewire;

test('chiudere la raccolta porta la rassegna in revisione', function () {
    Livewire::actingAs(User::factory()->operatore()->create());
    $rassegna = Rassegna::factory()->create(['stato' => StatoRassegna::InRaccolta]);

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])->call('chiudiRaccolta');

    expect($rassegna->fresh()->stato)->toBe(StatoRassegna::InRevisione);
    expect(LogAzione::where('azione', 'chiude_raccolta')->exists())->toBeTrue();
});

test('non si chiude la rassegna senza un PDF generato', function () {
    Livewire::actingAs(User::factory()->operatore()->create());
    $rassegna = Rassegna::factory()->create(['stato' => StatoRassegna::InRevisione]);

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])->call('chiudiRassegna');

    expect($rassegna->fresh()->stato)->toBe(StatoRassegna::InRevisione); // invariata
});

test('con un PDF generato la rassegna si chiude', function () {
    Livewire::actingAs($u = User::factory()->create());
    $rassegna = Rassegna::factory()->create(['stato' => StatoRassegna::InRevisione]);
    DocumentoGenerato::factory()->for($rassegna)->create(['generato_da' => $u->id]);

    Livewire::test(Scheda::class, ['rassegna' => $rassegna])->call('chiudiRassegna');

    expect($rassegna->fresh()->stato)->toBe(StatoRassegna::Chiusa);
    expect(LogAzione::where('azione', 'chiude_rassegna')->exists())->toBeTrue();
});

test('solo il supervisore riapre una rassegna chiusa', function () {
    $rassegna = Rassegna::factory()->create(['stato' => StatoRassegna::Chiusa]);

    // Operatore: vietato.
    Livewire::actingAs(User::factory()->operatore()->create());
    Livewire::test(Scheda::class, ['rassegna' => $rassegna])
        ->call('riapri')
        ->assertForbidden();
    expect($rassegna->fresh()->stato)->toBe(StatoRassegna::Chiusa);

    // Supervisore: riapre.
    Livewire::actingAs(User::factory()->supervisore()->create());
    Livewire::test(Scheda::class, ['rassegna' => $rassegna])->call('riapri');
    expect($rassegna->fresh()->stato)->toBe(StatoRassegna::Riaperta);
    expect(LogAzione::where('azione', 'riapre_rassegna')->exists())->toBeTrue();
});
