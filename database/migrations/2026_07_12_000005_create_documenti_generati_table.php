<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DocumentoGenerato — il PDF della rassegna. Versionato: non si sovrascrive mai
 * (docs/modello-dati.md § DocumentoGenerato, regole-business.md §9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documenti_generati', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rassegna_id')->constrained('rassegne');
            $table->unsignedInteger('versione'); // 1, 2, 3…
            $table->string('file_path'); // su disco Laravel
            $table->foreignId('generato_da')->constrained('users');
            $table->dateTime('generato_il');
            // L'invio al cliente è manuale, fuori dal sistema.
            $table->dateTime('scaricato_il')->nullable();
            // Snapshot degli id inclusi: cosa è stato effettivamente consegnato.
            $table->json('uscite_incluse');
            $table->timestamps();

            $table->unique(['rassegna_id', 'versione']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documenti_generati');
    }
};
