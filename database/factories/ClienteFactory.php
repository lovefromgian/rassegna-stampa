<?php

namespace Database\Factories;

use App\Enums\StatoCliente;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->company(),
            'referente' => fake()->name(),
            'email_referente' => fake()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'destinatari_invio' => [fake()->safeEmail()],
            'logo_path' => null,
            'colore_accento' => fake()->hexColor(),
            'note' => null,
            'stato' => StatoCliente::Attivo,
        ];
    }

    public function archiviato(): static
    {
        return $this->state(fn (array $attributes) => [
            'stato' => StatoCliente::Archiviato,
        ]);
    }
}
