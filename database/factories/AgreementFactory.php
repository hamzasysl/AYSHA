<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AgreementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => 'recurring',
            'status' => 'active',
            'm2' => 300,
            'm2_rate' => 100,
            'amount' => 30000,
            'vat_rate' => 20,
        ];
    }
}
