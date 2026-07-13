<?php

use App\Enums\StatoRassegna;
use App\Jobs\ScansionaRassegna;
use App\Models\Rassegna;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;

test('il comando accoda solo le rassegne in raccolta con periodo attivo', function () {
    Queue::fake();

    // Attiva: in raccolta, oggi dentro il periodo.
    $attiva = Rassegna::factory()->create([
        'stato' => StatoRassegna::InRaccolta,
        'monitoraggio_inizio' => now()->subDays(2)->toDateString(),
        'monitoraggio_fine' => now()->addDays(5)->toDateString(),
    ]);

    // In raccolta ma periodo scaduto.
    Rassegna::factory()->create([
        'stato' => StatoRassegna::InRaccolta,
        'monitoraggio_inizio' => now()->subDays(30)->toDateString(),
        'monitoraggio_fine' => now()->subDays(10)->toDateString(),
    ]);

    // Periodo attivo ma già in revisione (raccolta finita).
    Rassegna::factory()->create([
        'stato' => StatoRassegna::InRevisione,
        'monitoraggio_inizio' => now()->subDays(2)->toDateString(),
        'monitoraggio_fine' => now()->addDays(5)->toDateString(),
    ]);

    $this->artisan('rassegne:scansiona')->assertSuccessful();

    Queue::assertPushed(ScansionaRassegna::class, 1);
    Queue::assertPushed(ScansionaRassegna::class, fn ($job) => $job->rassegna->is($attiva));
});

test('la scansione giornaliera è schedulata', function () {
    $eventi = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains($e->command ?? '', 'rassegne:scansiona'));

    expect($eventi)->not->toBeEmpty();
});
