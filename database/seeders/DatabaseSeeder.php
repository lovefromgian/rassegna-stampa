<?php

namespace Database\Seeders;

use App\Enums\RuoloUtente;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Utenti demo per lo sviluppo: un supervisore e un operatore.
     * In produzione gli utenti li crea l'agenzia (nessuna auto-registrazione).
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'supervisore@example.com'],
            [
                'name' => 'Marco (Supervisore)',
                'password' => Hash::make('password'),
                'ruolo' => RuoloUtente::Supervisore,
            ],
        );

        User::firstOrCreate(
            ['email' => 'operatore@example.com'],
            [
                'name' => 'Lucia (Operatore)',
                'password' => Hash::make('password'),
                'ruolo' => RuoloUtente::Operatore,
            ],
        );
    }
}
