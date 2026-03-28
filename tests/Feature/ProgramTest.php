<?php

use App\Enums\EnrollmentStatus;
use App\Enums\ProspectStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Prospect;
use App\Models\User;
use Livewire\Livewire;

// ── Program CRUD ──────────────────────────────────────────────────────

test('guests are redirected from program create', function () {
    $this->get(route('programs.create'))->assertRedirect(route('login'));
});

test('staff cannot create a program', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('programs.create'))
        ->assertForbidden();
});

test('admin can create a program', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::programs.create')
        ->set('name', 'Welding 101')
        ->set('description', 'A welding program.')
        ->call('save');

    expect(Program::where('name', 'Welding 101')->exists())->toBeTrue();
});

test('admin can create a program with default tuition', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::programs.create')
        ->set('name', 'Electrical Trades')
        ->set('default_tuition', '7500.00')
        ->call('save');

    $program = Program::where('name', 'Electrical Trades')->first();

    expect($program)->not->toBeNull()
        ->and($program->default_tuition)->toBe('7500.00');
});

test('admin can update a program', function () {
    $admin = User::factory()->admin()->create();
    $program = Program::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($admin)
        ->test('pages::programs.edit', ['program' => $program])
        ->set('name', 'New Name')
        ->call('save');

    expect($program->fresh()->name)->toBe('New Name');
});

test('admin can delete a program without cohorts', function () {
    $admin = User::factory()->admin()->create();
    $program = Program::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::cohorts.index')
        ->assertSee('Delete')
        ->call('deleteProgram', $program->id);

    expect(Program::find($program->id))->toBeNull();
});

test('admin cannot delete a program with cohorts', function () {
    $admin = User::factory()->admin()->create();
    $program = Program::factory()->create();
    Cohort::factory()->for($program)->create();

    Livewire::actingAs($admin)
        ->test('pages::cohorts.index')
        ->call('deleteProgram', $program->id)
        ->assertForbidden();

    expect(Program::find($program->id))->not->toBeNull();
});

// ── Tuition Pre-fill ──────────────────────────────────────────────────

test('enrolling pre-fills tuition from program default', function () {
    $admin = User::factory()->admin()->create();
    $program = Program::factory()->create(['default_tuition' => 5000.00]);
    $cohort = Cohort::factory()->for($program)->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Qualified]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll');

    $enrollment = Enrollment::where('prospect_id', $prospect->id)->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->amount_owed)->toBe('5000.00')
        ->and($enrollment->status)->toBe(EnrollmentStatus::Pending);
});

test('enrolling without default tuition leaves amount_owed null', function () {
    $admin = User::factory()->admin()->create();
    $program = Program::factory()->create(['default_tuition' => null]);
    $cohort = Cohort::factory()->for($program)->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Qualified]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll');

    $enrollment = Enrollment::where('prospect_id', $prospect->id)->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->amount_owed)->toBeNull();
});

// ── Program Filtering ─────────────────────────────────────────────────

test('prospects index filters by program', function () {
    $admin = User::factory()->admin()->create();
    $programA = Program::factory()->create(['name' => 'Program A']);
    $programB = Program::factory()->create(['name' => 'Program B']);
    $cohortA = Cohort::factory()->for($programA)->create();
    $cohortB = Cohort::factory()->for($programB)->create();
    Prospect::factory()->create(['name' => 'Alice', 'cohort_id' => $cohortA->id]);
    Prospect::factory()->create(['name' => 'Bob', 'cohort_id' => $cohortB->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.index')
        ->set('programFilter', (string) $programA->id)
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

test('prospects index shows all prospects when no program filter', function () {
    $admin = User::factory()->admin()->create();
    $programA = Program::factory()->create();
    $programB = Program::factory()->create();
    $cohortA = Cohort::factory()->for($programA)->create();
    $cohortB = Cohort::factory()->for($programB)->create();
    Prospect::factory()->create(['name' => 'Alice', 'cohort_id' => $cohortA->id]);
    Prospect::factory()->create(['name' => 'Bob', 'cohort_id' => $cohortB->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.index')
        ->assertSee('Alice')
        ->assertSee('Bob');
});

test('finance page filters by program', function () {
    $admin = User::factory()->admin()->create();
    $programA = Program::factory()->create(['default_tuition' => 5000]);
    $programB = Program::factory()->create(['default_tuition' => 8000]);
    $cohortA = Cohort::factory()->for($programA)->create();
    $cohortB = Cohort::factory()->for($programB)->create();

    $prospectA = Prospect::factory()->create(['name' => 'Alice', 'status' => ProspectStatus::Enrolled, 'cohort_id' => $cohortA->id]);
    Enrollment::factory()->for($prospectA)->create(['cohort_id' => $cohortA->id, 'amount_owed' => 5000]);

    $prospectB = Prospect::factory()->create(['name' => 'Bob', 'status' => ProspectStatus::Enrolled, 'cohort_id' => $cohortB->id]);
    Enrollment::factory()->for($prospectB)->create(['cohort_id' => $cohortB->id, 'amount_owed' => 8000]);

    Livewire::actingAs($admin)
        ->test('pages::finance.index')
        ->set('programFilter', (string) $programA->id)
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

test('reports enrollment trends filters by program', function () {
    $admin = User::factory()->admin()->create();
    $programA = Program::factory()->create();
    $programB = Program::factory()->create();
    $cohortA = Cohort::factory()->for($programA)->create();
    $cohortB = Cohort::factory()->for($programB)->create();

    Enrollment::factory()->create(['cohort_id' => $cohortA->id, 'enrolled_at' => now()]);
    Enrollment::factory()->create(['cohort_id' => $cohortA->id, 'enrolled_at' => now()]);
    Enrollment::factory()->create(['cohort_id' => $cohortB->id, 'enrolled_at' => now()]);

    $component = Livewire::actingAs($admin)
        ->test('pages::reports.index')
        ->set('programFilter', (string) $programA->id);

    $summary = $component->get('enrollmentSummary');

    expect($summary['this_month'])->toBe(2);
});
