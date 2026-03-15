<?php

use App\Enums\ProspectStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\MilestoneRecord;
use App\Models\Prospect;
use App\Models\User;
use Livewire\Livewire;

test('admin can add a milestone', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('milestoneTitle', 'Module 1 Complete')
        ->call('addMilestone')
        ->assertHasNoErrors();

    expect(MilestoneRecord::where('enrollment_id', Enrollment::where('prospect_id', $prospect->id)->value('id'))->exists())->toBeTrue();
});

test('staff cannot add a milestone', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled, 'assigned_to' => $staff->id]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('milestoneTitle', 'Module 1 Complete')
        ->call('addMilestone')
        ->assertForbidden();
});

test('admin can mark a milestone as complete', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    $enrollment = Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);
    $milestone = MilestoneRecord::factory()->create(['enrollment_id' => $enrollment->id, 'completed_at' => null]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->call('completeMilestone', $milestone->id)
        ->assertHasNoErrors();

    expect($milestone->fresh()->completed_at)->not->toBeNull();
});

test('milestone requires a title', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('milestoneTitle', '')
        ->call('addMilestone')
        ->assertHasErrors(['milestoneTitle']);
});
