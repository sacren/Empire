<?php

use App\Enums\EnrollmentStatus;
use App\Enums\ProspectStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Prospect;
use App\Models\User;
use Livewire\Livewire;

test('admin can enroll a qualified prospect', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Qualified]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll')
        ->assertHasNoErrors();
});

test('staff can enroll their assigned qualified prospect', function () {
    $staff = User::factory()->staff()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Qualified,
        'assigned_to' => $staff->id,
    ]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll')
        ->assertHasNoErrors();
});

test('staff cannot access a prospect assigned to someone else', function () {
    $staff = User::factory()->staff()->create();
    $otherStaff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Qualified,
        'assigned_to' => $otherStaff->id,
    ]);

    $this->actingAs($staff)
        ->get(route('prospects.show', $prospect))
        ->assertForbidden();
});

test('staff cannot enroll a non-qualified prospect', function () {
    $staff = User::factory()->staff()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::InProgress,
        'assigned_to' => $staff->id,
    ]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll')
        ->assertForbidden();
});

test('cannot enroll an already-enrolled prospect', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Qualified]);
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll')
        ->assertForbidden();
});

test('enrolling sets prospect status to enrolled', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Qualified]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll');

    expect($prospect->fresh()->status)->toBe(ProspectStatus::Enrolled);
});

test('enrolling creates an enrollment record with correct cohort and pending status', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Qualified]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll');

    $enrollment = Enrollment::where('prospect_id', $prospect->id)->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->cohort_id)->toBe($cohort->id)
        ->and($enrollment->status)->toBe(EnrollmentStatus::Pending);
});

test('enrollment requires a cohort to be selected', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Qualified]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', '')
        ->call('enroll')
        ->assertHasErrors(['enrollCohortId']);
});
