<?php

namespace Database\Factories;

use App\Enums\ContactMethod;
use App\Enums\ContactTime;
use App\Enums\EducationLevel;
use App\Enums\EmploymentStatus;
use App\Enums\ProspectEntryPoint;
use App\Enums\ProspectStatus;
use App\Models\Cohort;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospect>
 */
class ProspectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->optional(0.7)->address(),
            'date_of_birth' => fake()->optional(0.8)->dateTimeBetween('-60 years', '-18 years')?->format('Y-m-d'),
            'gender' => fake()->optional(0.7)->randomElement(['male', 'female', 'non_binary', 'other']),
            'emergency_contact_name' => fake()->optional(0.6)->name(),
            'emergency_contact_phone' => fake()->optional(0.6)->phoneNumber(),
            'preferred_contact_method' => fake()->randomElement(ContactMethod::cases())->value,
            'best_time_to_contact' => fake()->randomElement(ContactTime::cases())->value,
            'heard_about_us' => fake()->optional(0.7)->randomElement(['internet_search', 'social_media', 'friend_family', 'advertisement', 'job_fair', 'other']),
            'cohort_id' => null,
            'highest_education_level' => fake()->optional(0.7, null)->randomElement(array_column(EducationLevel::cases(), 'value')),
            'employment_status' => fake()->optional(0.7, null)->randomElement(array_column(EmploymentStatus::cases(), 'value')),
            'financing_interest' => fake()->boolean(30),
            'goals' => fake()->optional(0.6)->paragraph(),
            'status' => fake()->randomElement(ProspectStatus::cases())->value,
            'entry_point' => fake()->randomElement(ProspectEntryPoint::cases())->value,
            'assigned_to' => null,
        ];
    }

    public function withCohort(): static
    {
        return $this->state(fn (array $attributes) => [
            'cohort_id' => Cohort::factory(),
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user->id,
        ]);
    }

    public function newStatus(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProspectStatus::New->value,
        ]);
    }
}
