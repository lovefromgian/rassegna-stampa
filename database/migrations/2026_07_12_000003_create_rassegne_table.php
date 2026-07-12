<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rassegna — contenitore vivo della campagna di monitoraggio. Soft delete.
 * Una per comunicato, oppure "di periodo" (docs/modello-dati.md § Rassegna).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rassegne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clienti');
            $table->string('titolo');

            // Comunicato: opzionale (rassegna di periodo senza comunicato).
            $table->string('comunicato_titolo')->nullable();
            $table->string('comunicato_sottotitolo')->nullable(); // copertina PDF
            $table->date('comunicato_data')->nullable();
            $table->text('comunicato_testo')->nullable(); // suggerisce le parole chiave
            $table->string('comunicato_file_path')->nullable(); // eventuale allegato

            // Parole chiave richieste + escluse (tagliano i falsi positivi).
            $table->json('parole_chiave');
            $table->json('parole_escluse')->nullable();

            // Periodo di monitoraggio: sempre inizio + fine (nessun caso speciale).
            $table->date('monitoraggio_inizio');
            $table->date('monitoraggio_fine');

            $table->string('stato')->default('in_raccolta'); // App\Enums\StatoRassegna
            $table->timestamps();
            $table->softDeletes();

            // Scheduler: seleziona le rassegne con periodo attivo.
            $table->index(['cliente_id', 'stato']);
            $table->index('monitoraggio_fine');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rassegne');
    }
};
