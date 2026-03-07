<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cohort>
 */
class CohortFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'name' => fake()->numerify('Cohort ##').' '.fake()->year(),
            'start_date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'capacity' => fake()->optional(0.7)->numberBetween(10, 30),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
