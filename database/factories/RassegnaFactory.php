<?php

namespace Database\Factories;

use App\Enums\StatoRassegna;
use App\Models\Cliente;
use App\Models\Rassegna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rassegna>
 */
class RassegnaFactory extends Factory
{
    protected $model = Rassegna::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inizio = fake()->dateTimeBetween('-1 month', 'now');
        $fine = (clone $inizio)->modify('+14 days');

        return [
            'cliente_id' => Cliente::factory(),
            'titolo' => fake()->sentence(4),
            'comunicato_titolo' => fake()->sentence(6),
            'comunicato_sottotitolo' => fake()->sentence(8),
            'comunicato_data' => $inizio,
            'comunicato_testo' => fake()->paragraph(),
            'parole_chiave' => [fake()->word(), fake()->word()],
            'parole_escluse' => [],
            'monitoraggio_inizio' => $inizio,
            'monitoraggio_fine' => $fine,
            'stato' => StatoRassegna::InRaccolta,
        ];
    }

    public function inRevisione(): static
    {
        return $this->state(fn (array $attributes) => [
            'stato' => StatoRassegna::InRevisione,
        ]);
    }

    public function chiusa(): static
    {
        return $this->state(fn (array $attributes) => [
            'stato' => StatoRassegna::Chiusa,
        ]);
    }
}
