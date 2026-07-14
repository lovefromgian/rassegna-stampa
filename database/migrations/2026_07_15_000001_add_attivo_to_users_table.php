<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flag di attivazione dell'utente. Disattivare un utente ne revoca l'accesso senza
 * cancellarlo: il log di audit (che lo referenzia) resta integro e immutabile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('attivo')->default(true)->after('ruolo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('attivo');
        });
    }
};
