<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uscita — l'entità centrale: ciò che il sistema raccoglie, l'operatore revisiona,
 * il PDF impagina. Soft delete: un'uscita scartata resta archiviata e recuperabile
 * (docs/modello-dati.md § Uscita).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uscite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rassegna_id')->constrained('rassegne');
            $table->foreignId('testata_id')->constrained('testate');

            $table->string('titolo'); // titolo dell'articolo
            $table->date('data_pubblicazione');
            $table->string('url')->nullable(); // solo online; unique per rassegna (deduplica)
            $table->string('tipo_media'); // App\Enums\TipoMedia
            $table->string('rilevanza')->nullable(); // assegnata in revisione
            $table->string('stato')->default('candidato'); // App\Enums\StatoUscita
            // 0-100, calcolato in scoperta; guida l'ordinamento dei candidati.
            $table->unsignedTinyInteger('punteggio_corrispondenza')->nullable();

            // File su disco Laravel (mai percorsi assoluti nel codice).
            $table->string('screenshot_path')->nullable(); // full-page: finisce nel PDF
            $table->string('pdf_pagina_path')->nullable(); // versione multipagina leggibile
            $table->longText('testo_estratto')->nullable(); // indicizzato full-text
            $table->string('file_caricato_path')->nullable(); // ritaglio / file sostituito a mano

            $table->string('pagina_giornale')->nullable(); // es. "pag 55" (solo carta)
            $table->text('errore_cattura')->nullable(); // messaggio leggibile se fallisce
            $table->integer('posizione_pdf')->nullable(); // ordine manuale nel PDF
            $table->text('note')->nullable(); // interne, visibili solo al team
            $table->dateTime('data_rilevamento'); // quando il sistema l'ha trovata

            $table->timestamps();
            $table->softDeletes();

            $table->index(['rassegna_id', 'stato']);
            // Deduplica: una URL già presente nella stessa rassegna non si ripropone.
            $table->unique(['rassegna_id', 'url']);
        });

        // Full-text su testo_estratto: abilita la ricerca d'archivio (M5).
        // SQLite (dev/test) non supporta gli indici FULLTEXT: solo su MySQL/MariaDB.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('uscite', function (Blueprint $table) {
                $table->fullText('testo_estratto');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uscite');
    }
};
