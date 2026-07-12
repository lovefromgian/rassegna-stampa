<?php

namespace Database\Factories;

use App\Models\DocumentoGenerato;
use App\Models\Rassegna;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentoGenerato>
 */
class DocumentoGeneratoFactory extends Factory
{
    protected $model = DocumentoGenerato::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rassegna_id' => Rassegna::factory(),
            'versione' => 1,
            'file_path' => 'rassegne/'.fake()->uuid().'.pdf',
            'generato_da' => User::factory(),
            'generato_il' => now(),
            'scaricato_il' => null,
            'uscite_incluse' => [],
        ];
    }
}
