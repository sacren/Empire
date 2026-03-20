<?php

namespace Database\Factories;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommunicationLog>
 */
class CommunicationLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prospect_id' => Prospect::factory(),
            'sent_by' => User::factory(),
            'channel' => CommunicationChannel::Email,
            'type' => fake()->randomElement(CommunicationType::cases())->value,
            'subject' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'sent_at' => now(),
        ];
    }

    public function automated(): static
    {
        return $this->state(fn () => [
            'sent_by' => null,
            'type' => CommunicationType::Automated,
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn () => [
            'type' => CommunicationType::Manual,
        ]);
    }

    public function bulk(): static
    {
        return $this->state(fn () => [
            'type' => CommunicationType::Bulk,
        ]);
    }
}
