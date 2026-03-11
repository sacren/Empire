<?php

namespace Database\Seeders;

use App\Models\Prospect;
use App\Services\RoundRobinAssignmentService;
use Illuminate\Database\Seeder;

class ProspectSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(RoundRobinAssignmentService::class);

        Prospect::factory(20)->make()->each(function (Prospect $prospect) use ($service) {
            $prospect->assigned_to = $service->nextStaffMember()?->id;
            $prospect->save();
        });
    }
}
