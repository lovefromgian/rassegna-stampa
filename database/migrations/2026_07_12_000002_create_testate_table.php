<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testata — creata automaticamente quando il sistema incontra una testata nuova,
 * correggibile a mano. Evita "Il Goriziano" e "Goriziano" come entità diverse e
 * abilita le statistiche per testata (docs/modello-dati.md § Testata).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testate', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('sito')->nullable(); // dominio
            $table->string('tipo_prevalente')->nullable(); // stessi valori di tipo_media
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testate');
    }
};
