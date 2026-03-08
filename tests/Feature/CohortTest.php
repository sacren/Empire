<?php

use App\Models\Cohort;
use App\Models\Program;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from cohorts index', function () {
    $this->get(route('cohorts.index'))->assertRedirect(route('login'));
});

test('admin can view cohorts index', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('cohorts.index'))
        ->assertOk();
});

test('staff cannot view cohorts index', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('cohorts.index'))
        ->assertForbidden();
});

test('admin can create a cohort', function () {
    $admin = User::factory()->admin()->create();
    $program = Program::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::cohorts.create')
        ->set('program_id', (string) $program->id)
        ->set('name', 'Spring 2026')
        ->set('start_date', '2026-03-01')
        ->call('save');

    expect(Cohort::where('name', 'Spring 2026')->exists())->toBeTrue();
});

test('staff cannot visit cohorts create page', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('cohorts.create'))
        ->assertForbidden();
});

test('admin can toggle cohort active status', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.index')
        ->call('toggleCohort', $cohort->id);

    expect($cohort->fresh()->is_active)->toBeFalse();
});

test('admin can delete a cohort', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::cohorts.index')
        ->call('deleteCohort', $cohort->id);

    expect(Cohort::find($cohort->id))->toBeNull();
});

test('admin can update a cohort', function () {
    $admin = User::factory()->admin()->create();
    $program = Program::factory()->create();
    $cohort = Cohort::factory()->for($program)->create(['name' => 'Old Name']);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.edit', ['cohort' => $cohort])
        ->set('name', 'New Name')
        ->call('save');

    expect($cohort->fresh()->name)->toBe('New Name');
});
