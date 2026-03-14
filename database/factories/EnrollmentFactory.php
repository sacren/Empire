<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Cohort;
use App\Models\Prospect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prospect_id' => Prospect::factory(),
            'cohort_id' => Cohort::factory(),
            'amount_owed' => null,
            'status' => EnrollmentStatus::Pending->value,
            'enrolled_at' => now(),
        ];
    }
}
