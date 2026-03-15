<?php

use App\Enums\AttendanceStatus;
use App\Enums\ProspectStatus;
use App\Models\AttendanceRecord;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Prospect;
use App\Models\User;
use Livewire\Livewire;

test('admin can add an attendance record', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('sessionDate', today()->format('Y-m-d'))
        ->set('sessionStatus', AttendanceStatus::Present->value)
        ->call('addAttendanceRecord')
        ->assertHasNoErrors();

    expect(AttendanceRecord::where('enrollment_id', Enrollment::where('prospect_id', $prospect->id)->value('id'))->exists())->toBeTrue();
});

test('staff cannot add an attendance record', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled, 'assigned_to' => $staff->id]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('sessionDate', today()->format('Y-m-d'))
        ->set('sessionStatus', AttendanceStatus::Present->value)
        ->call('addAttendanceRecord')
        ->assertForbidden();
});

test('attendance record is stored with correct data', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    $enrollment = Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('sessionDate', '2026-03-10')
        ->set('sessionStatus', AttendanceStatus::Late->value)
        ->set('sessionNotes', 'Arrived 15 minutes late')
        ->call('addAttendanceRecord');

    $record = AttendanceRecord::where('enrollment_id', $enrollment->id)->first();
    expect($record)->not->toBeNull()
        ->and($record->status)->toBe(AttendanceStatus::Late)
        ->and($record->notes)->toBe('Arrived 15 minutes late');
});

test('attendance requires session date and status', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('sessionDate', '')
        ->set('sessionStatus', '')
        ->call('addAttendanceRecord')
        ->assertHasErrors(['sessionDate', 'sessionStatus']);
});

test('multiple attendance records can exist for an enrollment', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    $enrollment = Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    AttendanceRecord::factory()->count(3)->create(['enrollment_id' => $enrollment->id]);

    expect(AttendanceRecord::where('enrollment_id', $enrollment->id)->count())->toBe(3);
});
