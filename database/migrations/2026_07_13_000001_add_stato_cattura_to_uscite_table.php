<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stato tecnico esplicito della cattura (App\Enums\StatoCattura), separato dal ciclo di
 * vita di business dell'Uscita (App\Enums\StatoUscita). Null = nessuna cattura web
 * prevista (media manuali). Vedi docs/modello-dati.md § Uscita.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uscite', function (Blueprint $table) {
            $table->string('stato_cattura')->nullable()->after('stato');
            $table->dateTime('cattura_completata_il')->nullable()->after('errore_cattura');
        });
    }

    public function down(): void
    {
        Schema::table('uscite', function (Blueprint $table) {
            $table->dropColumn(['stato_cattura', 'cattura_completata_il']);
        });
    }
};
