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

    AttendanceRecord::factory()->create(['enrollment_id' => $enrollment->id, 'session_date' => '2026-03-01']);
    AttendanceRecord::factory()->create(['enrollment_id' => $enrollment->id, 'session_date' => '2026-03-02']);
    AttendanceRecord::factory()->create(['enrollment_id' => $enrollment->id, 'session_date' => '2026-03-03']);

    expect(AttendanceRecord::where('enrollment_id', $enrollment->id)->count())->toBe(3);
});
