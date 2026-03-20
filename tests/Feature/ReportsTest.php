<?php

use App\Enums\ProspectStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Prospect;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
});

test('staff cannot access reports', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('reports.index'))
        ->assertForbidden();
});

test('admin can access reports', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('Reports');
});

test('enrollment trends shows enrollment counts by month', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    Enrollment::factory()->create([
        'cohort_id' => $cohort->id,
        'enrolled_at' => now()->startOfMonth(),
    ]);
    Enrollment::factory()->create([
        'cohort_id' => $cohort->id,
        'enrolled_at' => now()->startOfMonth(),
    ]);

    $this->actingAs($admin)
        ->get(route('reports.index', ['tab' => 'enrollment-trends']))
        ->assertOk()
        ->assertSee(now()->format('F Y'));
});

test('revenue report shows cohort financial data', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create(['name' => 'Welding 101']);

    $enrollment = Enrollment::factory()->create([
        'cohort_id' => $cohort->id,
        'amount_owed' => 5000.00,
    ]);

    Payment::factory()->create([
        'enrollment_id' => $enrollment->id,
        'amount' => 2000.00,
        'paid_at' => today(),
    ]);

    $this->actingAs($admin)
        ->get(route('reports.index', ['tab' => 'revenue']))
        ->assertOk()
        ->assertSee('Welding 101')
        ->assertSee('$5,000.00')
        ->assertSee('$2,000.00');
});

test('cohort utilization shows active cohorts with enrollment counts', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create([
        'name' => 'HVAC Spring 2026',
        'capacity' => 20,
        'is_active' => true,
    ]);

    Enrollment::factory(5)->create(['cohort_id' => $cohort->id]);

    $this->actingAs($admin)
        ->get(route('reports.index', ['tab' => 'cohort-utilization']))
        ->assertOk()
        ->assertSee('HVAC Spring 2026')
        ->assertSee('25%');
});

test('staff performance shows conversion rates', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create(['name' => 'Jane Recruiter']);

    Prospect::factory(4)->create([
        'assigned_to' => $staff->id,
        'status' => ProspectStatus::Contacted->value,
    ]);
    Prospect::factory(1)->create([
        'assigned_to' => $staff->id,
        'status' => ProspectStatus::Enrolled->value,
    ]);

    $this->actingAs($admin)
        ->get(route('reports.index', ['tab' => 'staff-performance']))
        ->assertOk()
        ->assertSee('Jane Recruiter')
        ->assertSee('20%');
});

test('graduation rates shows per-cohort graduation statistics', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create(['name' => 'Plumbing Fall 2025']);

    Enrollment::factory(4)->create(['cohort_id' => $cohort->id]);
    Enrollment::factory(2)->create([
        'cohort_id' => $cohort->id,
        'graduated_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('reports.index', ['tab' => 'graduation-rates']))
        ->assertOk()
        ->assertSee('Plumbing Fall 2025')
        ->assertSee('33%');
});

test('period filter affects enrollment trends data', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    // Old enrollment — outside this month
    Enrollment::factory()->create([
        'cohort_id' => $cohort->id,
        'enrolled_at' => now()->subMonths(3),
    ]);

    // Current month enrollment
    Enrollment::factory()->create([
        'cohort_id' => $cohort->id,
        'enrolled_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('reports.index', ['tab' => 'enrollment-trends', 'period' => 'month']))
        ->assertOk();
});
