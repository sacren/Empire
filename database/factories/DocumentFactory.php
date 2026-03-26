<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prospect_id' => Prospect::factory(),
            'enrollment_id' => null,
            'uploaded_by' => User::factory(),
            'type' => DocumentType::Other,
            'status' => DocumentStatus::Pending,
            'original_filename' => fake()->word() . '.pdf',
            'disk_path' => 'documents/' . fake()->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1024, 5242880),
            'notes' => null,
        ];
    }

    public function enrollmentAgreement(): static
    {
        return $this->state(fn () => ['type' => DocumentType::EnrollmentAgreement]);
    }

    public function studentId(): static
    {
        return $this->state(fn () => ['type' => DocumentType::StudentId]);
    }

    public function certificate(): static
    {
        return $this->state(fn () => ['type' => DocumentType::Certificate]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => DocumentStatus::Approved,
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => DocumentStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
        ]);
    }
}
