<?php

use App\Enums\ProspectStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Prospect;
use App\Models\User;
use Livewire\Livewire;

test('admin can graduate an enrolled student', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('graduationDate', today()->format('Y-m-d'))
        ->call('graduate')
        ->assertHasNoErrors();
});

test('staff cannot graduate a student', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled, 'assigned_to' => $staff->id]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('graduationDate', today()->format('Y-m-d'))
        ->call('graduate')
        ->assertForbidden();
});

test('graduating sets prospect status to graduated', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('graduationDate', today()->format('Y-m-d'))
        ->call('graduate');

    expect($prospect->fresh()->status)->toBe(ProspectStatus::Graduated);
});

test('graduating records graduation date and certificate info', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    $enrollment = Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('graduationDate', '2026-03-14')
        ->set('certificateNumber', 'CERT-2026-001')
        ->set('certificateIssuedAt', '2026-03-14')
        ->call('graduate');

    $fresh = $enrollment->fresh();
    expect($fresh->graduated_at)->not->toBeNull()
        ->and($fresh->certificate_number)->toBe('CERT-2026-001')
        ->and($fresh->certificate_issued_at->format('Y-m-d'))->toBe('2026-03-14');
});

test('graduation requires a graduation date', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('graduationDate', '')
        ->call('graduate')
        ->assertHasErrors(['graduationDate']);
});
