<?php

namespace Database\Factories;

use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use App\Models\Rassegna;
use App\Models\Testata;
use App\Models\Uscita;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Uscita>
 */
class UscitaFactory extends Factory
{
    protected $model = Uscita::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rassegna_id' => Rassegna::factory(),
            'testata_id' => Testata::factory(),
            'titolo' => fake()->sentence(6),
            'data_pubblicazione' => fake()->dateTimeBetween('-1 month', 'now'),
            'url' => fake()->unique()->url(),
            'tipo_media' => TipoMedia::Online,
            'rilevanza' => null,
            'stato' => StatoUscita::Candidato,
            'punteggio_corrispondenza' => fake()->numberBetween(0, 100),
            'data_rilevamento' => now(),
        ];
    }

    public function confermato(): static
    {
        return $this->state(fn (array $attributes) => [
            'stato' => StatoUscita::Confermato,
        ]);
    }

    public function catturato(): static
    {
        return $this->state(fn (array $attributes) => [
            'stato' => StatoUscita::Catturato,
            'screenshot_path' => 'screenshots/'.fake()->uuid().'.png',
            'testo_estratto' => fake()->paragraphs(3, true),
        ]);
    }

    public function approvato(): static
    {
        return $this->state(fn (array $attributes) => [
            'stato' => StatoUscita::Approvato,
            'screenshot_path' => 'screenshots/'.fake()->uuid().'.png',
            'testo_estratto' => fake()->paragraphs(3, true),
            'rilevanza' => \App\Enums\Rilevanza::Principale,
        ]);
    }

    public function scartato(): static
    {
        return $this->state(fn (array $attributes) => [
            'stato' => StatoUscita::Scartato,
        ]);
    }

    /** Uscita manuale (carta, radio, TV, agenzia): niente URL, ma file caricato. */
    public function manuale(TipoMedia $tipo = TipoMedia::Carta): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_media' => $tipo,
            'url' => null,
            'file_caricato_path' => 'ritagli/'.fake()->uuid().'.pdf',
        ]);
    }
}
