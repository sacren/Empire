<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\MilestoneRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MilestoneRecord>
 */
class MilestoneRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'title' => fake()->sentence(3),
            'completed_at' => null,
            'notes' => null,
        ];
    }
}
