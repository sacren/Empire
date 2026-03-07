<?php

namespace Database\Seeders;

use App\Models\Cohort;
use App\Models\Program;
use Illuminate\Database\Seeder;

class CohortSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::where('name', 'SkillPath')->first();

        if (! $program) {
            return;
        }

        $cohorts = [
            ['name' => 'Spring 2026', 'start_date' => '2026-03-01', 'capacity' => 20],
            ['name' => 'Summer 2026', 'start_date' => '2026-06-01', 'capacity' => 20],
            ['name' => 'Fall 2026', 'start_date' => '2026-09-01', 'capacity' => 20],
        ];

        foreach ($cohorts as $cohort) {
            Cohort::create([
                ...$cohort,
                'program_id' => $program->id,
                'is_active' => true,
            ]);
        }
    }
}
