<?php

namespace Database\Factories;

use App\Enums\TipoMedia;
use App\Models\Testata;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testata>
 */
class TestataFactory extends Factory
{
    protected $model = Testata::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nome = fake()->unique()->company();

        return [
            'nome' => $nome,
            'sito' => fake()->domainName(),
            'tipo_prevalente' => TipoMedia::Online,
            'logo_path' => null,
        ];
    }
}
