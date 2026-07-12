<?php

namespace Database\Factories;

use App\Models\LogAzione;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogAzione>
 */
class LogAzioneFactory extends Factory
{
    protected $model = LogAzione::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'azione' => 'azione_test',
            'entita_tipo' => 'sistema',
            'entita_id' => null,
            'dettagli' => null,
        ];
    }
}
