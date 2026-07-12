<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cliente — anagrafica. Soft delete (docs/modello-dati.md § Cliente).
 * Le impostazioni grafiche (logo, colore) sono ereditate da ogni rassegna del cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clienti', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('referente')->nullable();
            $table->string('email_referente')->nullable();
            $table->string('telefono')->nullable();
            // Lista di email a cui va consegnata la rassegna.
            $table->json('destinatari_invio')->nullable();
            // File su disco Laravel (mai percorsi assoluti); usato in copertina PDF.
            $table->string('logo_path')->nullable();
            // Hex; usato per bordi e intestazioni del PDF.
            $table->string('colore_accento')->nullable();
            $table->text('note')->nullable();
            $table->string('stato')->default('attivo'); // attivo | archiviato
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clienti');
    }
};
