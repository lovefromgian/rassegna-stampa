<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LogAzione (audit) — immutabile. Nessuno lo modifica né lo cancella, nemmeno il
 * supervisore (docs/modello-dati.md § LogAzione, regole-business.md §11).
 * L'immutabilità è garantita a livello di modello (nessun update/delete) e Policy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_azioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // chi
            $table->string('azione'); // conferma_candidato, scarto_uscita, genera_pdf, …
            $table->string('entita_tipo'); // classe del modello toccato
            $table->unsignedBigInteger('entita_id')->nullable();
            $table->json('dettagli')->nullable(); // contesto (es. valori cambiati)
            $table->dateTime('created_at'); // quando (nessun updated_at: immutabile)

            $table->index(['entita_tipo', 'entita_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_azioni');
    }
};
