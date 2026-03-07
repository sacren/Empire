<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@empiretradeschool.com',
        ]);

        // Staff accounts
        User::factory()->staff()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@empiretradeschool.com',
        ]);

        User::factory()->staff()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob@empiretradeschool.com',
        ]);

        $this->call([
            ProgramSeeder::class,
            CohortSeeder::class,
            ProspectSeeder::class,
        ]);
    }
}
