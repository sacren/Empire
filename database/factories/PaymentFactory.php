<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'amount' => fake()->randomFloat(2, 500, 5000),
            'method' => fake()->randomElement(PaymentMethod::cases())->value,
            'paid_at' => today()->format('Y-m-d'),
        ];
    }
}
