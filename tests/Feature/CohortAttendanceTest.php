<?php

use App\Enums\AttendanceStatus;
use App\Enums\ProspectStatus;
use App\Models\AttendanceRecord;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Prospect;
use App\Models\User;
use Livewire\Livewire;

test('admin can record attendance for all students in a cohort', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    $enrollments = collect([
        createCohortEnrollment($cohort),
        createCohortEnrollment($cohort),
        createCohortEnrollment($cohort),
    ]);

    $records = [];
    foreach ($enrollments as $enrollment) {
        $records[$enrollment->id] = ['status' => AttendanceStatus::Present->value, 'notes' => ''];
    }

    Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->set('sessionDate', '2026-03-18')
        ->set('records', $records)
        ->call('saveAttendance')
        ->assertHasNoErrors();

    foreach ($enrollments as $enrollment) {
        expect(AttendanceRecord::where('enrollment_id', $enrollment->id)->where('session_date', '2026-03-18')->exists())->toBeTrue(
            "Expected attendance record for enrollment {$enrollment->id}"
        );
    }
});

test('attendance records contain correct data', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $enrollment = createCohortEnrollment($cohort);

    $records = [
        $enrollment->id => ['status' => AttendanceStatus::Late->value, 'notes' => 'Arrived 15 minutes late'],
    ];

    Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->set('sessionDate', '2026-03-10')
        ->set('records', $records)
        ->call('saveAttendance');

    $record = AttendanceRecord::where('enrollment_id', $enrollment->id)->first();

    expect($record)->not->toBeNull()
        ->and($record->session_date->format('Y-m-d'))->toBe('2026-03-10')
        ->and($record->status)->toBe(AttendanceStatus::Late)
        ->and($record->notes)->toBe('Arrived 15 minutes late');
});

test('empty notes are stored as null', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $enrollment = createCohortEnrollment($cohort);

    $records = [
        $enrollment->id => ['status' => AttendanceStatus::Present->value, 'notes' => ''],
    ];

    Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->set('sessionDate', '2026-03-10')
        ->set('records', $records)
        ->call('saveAttendance');

    $record = AttendanceRecord::where('enrollment_id', $enrollment->id)->first();

    expect($record->notes)->toBeNull();
});

test('recording same date again updates existing records', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $enrollment = createCohortEnrollment($cohort);

    AttendanceRecord::factory()->create([
        'enrollment_id' => $enrollment->id,
        'session_date' => '2026-03-10',
        'status' => AttendanceStatus::Present->value,
    ]);

    $records = [
        $enrollment->id => ['status' => AttendanceStatus::Absent->value, 'notes' => 'Called in sick'],
    ];

    Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->set('sessionDate', '2026-03-10')
        ->set('records', $records)
        ->call('saveAttendance');

    expect(AttendanceRecord::where('enrollment_id', $enrollment->id)->where('session_date', '2026-03-10')->count())->toBe(1);

    $record = AttendanceRecord::where('enrollment_id', $enrollment->id)->first();
    expect($record->status)->toBe(AttendanceStatus::Absent)
        ->and($record->notes)->toBe('Called in sick');
});

test('changing date loads existing records', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $enrollment = createCohortEnrollment($cohort);

    AttendanceRecord::factory()->create([
        'enrollment_id' => $enrollment->id,
        'session_date' => '2026-03-05',
        'status' => AttendanceStatus::Excused->value,
        'notes' => 'Doctor appointment',
    ]);

    $component = Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->set('sessionDate', '2026-03-05');

    $records = $component->get('records');

    expect($records[$enrollment->id]['status'])->toBe(AttendanceStatus::Excused->value)
        ->and($records[$enrollment->id]['notes'])->toBe('Doctor appointment');
});

test('only enrollments from the target cohort are included', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $otherCohort = Cohort::factory()->create();

    $enrollment = createCohortEnrollment($cohort);
    $otherEnrollment = createCohortEnrollment($otherCohort);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->set('sessionDate', '2026-03-18')
        ->call('saveAttendance');

    expect(AttendanceRecord::where('enrollment_id', $enrollment->id)->exists())->toBeTrue()
        ->and(AttendanceRecord::where('enrollment_id', $otherEnrollment->id)->exists())->toBeFalse();
});

test('saving attendance shows success message', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    createCohortEnrollment($cohort);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->call('saveAttendance')
        ->assertSee('Attendance saved successfully.');
});

test('non-admin cannot access attendance page', function () {
    $staff = User::factory()->staff()->create();
    $cohort = Cohort::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->assertForbidden();
});

test('session date is required to save attendance', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    createCohortEnrollment($cohort);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->set('sessionDate', '')
        ->call('saveAttendance')
        ->assertHasErrors(['sessionDate']);
});

test('recent sessions list shows recorded dates with status summary', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $enrollment = createCohortEnrollment($cohort);

    AttendanceRecord::factory()->create([
        'enrollment_id' => $enrollment->id,
        'session_date' => '2026-03-15',
        'status' => AttendanceStatus::Present->value,
    ]);
    AttendanceRecord::factory()->create([
        'enrollment_id' => $enrollment->id,
        'session_date' => '2026-03-14',
        'status' => AttendanceStatus::Absent->value,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->assertSee('Recent Sessions')
        ->assertSee('Mar 15, 2026')
        ->assertSee('Mar 14, 2026');
});

test('clicking a past session date loads its records into the form', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $enrollment = createCohortEnrollment($cohort);

    AttendanceRecord::factory()->create([
        'enrollment_id' => $enrollment->id,
        'session_date' => '2026-03-12',
        'status' => AttendanceStatus::Late->value,
        'notes' => 'Bus was late',
    ]);

    $component = Livewire::actingAs($admin)
        ->test('pages::cohorts.attendance', ['cohort' => $cohort])
        ->call('loadSession', '2026-03-12');

    expect($component->get('sessionDate'))->toBe('2026-03-12');

    $records = $component->get('records');
    expect($records[$enrollment->id]['status'])->toBe(AttendanceStatus::Late->value)
        ->and($records[$enrollment->id]['notes'])->toBe('Bus was late');
});

function createCohortEnrollment(Cohort $cohort): Enrollment
{
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled, 'cohort_id' => $cohort->id]);

    return Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);
}
