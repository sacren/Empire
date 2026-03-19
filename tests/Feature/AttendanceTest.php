<?php

use App\Enums\ProspectStatus;
use App\Models\AttendanceRecord;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Prospect;

test('multiple attendance records can exist for an enrollment', function () {
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    $enrollment = Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    AttendanceRecord::factory()
        ->count(3)
        ->sequence(
            ['session_date' => '2026-03-01'],
            ['session_date' => '2026-03-02'],
            ['session_date' => '2026-03-03'],
        )
        ->create(['enrollment_id' => $enrollment->id]);

    expect(AttendanceRecord::where('enrollment_id', $enrollment->id)->count())->toBe(3);
});
