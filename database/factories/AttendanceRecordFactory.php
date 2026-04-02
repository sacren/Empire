<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'session_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'status' => fake()->randomElement(AttendanceStatus::cases())->value,
            'notes' => null,
        ];
    }
}
