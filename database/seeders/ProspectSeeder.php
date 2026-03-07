<?php

namespace Database\Seeders;

use App\Models\Prospect;
use Illuminate\Database\Seeder;

class ProspectSeeder extends Seeder
{
    public function run(): void
    {
        Prospect::factory(20)->create();
    }
}
