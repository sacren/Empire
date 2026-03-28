<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        Program::create([
            'name' => 'SkillPath',
            'description' => 'A comprehensive trade skills program for career changers and first-time learners.',
            'is_active' => true,
            'default_tuition' => 5000.00,
        ]);
    }
}
