<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `url` e `titolo` arrivano dalla scoperta (Google News RSS): gli URL di redirect
 * superano spesso i 255 caratteri e i titoli possono essere lunghi. Su SQLite la
 * lunghezza dei varchar non è applicata, ma su PostgreSQL (produzione) varchar(255)
 * è rigido e l'inserimento fallisce (SQLSTATE 22001). Si passa a text.
 * L'indice unique (rassegna_id, url) per la deduplica resta valido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uscite', function (Blueprint $table) {
            $table->text('titolo')->change();
            $table->text('url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('uscite', function (Blueprint $table) {
            $table->string('titolo')->change();
            $table->string('url')->nullable()->change();
        });
    }
};
