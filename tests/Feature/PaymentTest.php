<?php

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProspectStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Prospect;
use App\Models\User;
use Livewire\Livewire;

test('admin can set tuition on an enrollment', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $enrollment = Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('tuitionAmount', '5000')
        ->call('setTuition')
        ->assertHasNoErrors();

    expect($enrollment->fresh()->amount_owed)->toEqual('5000.00');
});

test('staff cannot set tuition', function () {
    $staff = User::factory()->staff()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Enrolled,
        'assigned_to' => $staff->id,
    ]);
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('tuitionAmount', '5000')
        ->call('setTuition')
        ->assertForbidden();
});

test('admin can record a payment', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'cohort_id' => $cohort->id,
        'amount_owed' => 5000,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('paymentAmount', '1000')
        ->set('paymentMethod', PaymentMethod::Cash->value)
        ->set('paymentDate', today()->format('Y-m-d'))
        ->call('addPayment')
        ->assertHasNoErrors();

    expect(Payment::where('enrollment_id', $enrollment->id)->exists())->toBeTrue();
});

test('staff cannot record a payment', function () {
    $staff = User::factory()->staff()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Enrolled,
        'assigned_to' => $staff->id,
    ]);
    Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'cohort_id' => $cohort->id,
        'amount_owed' => 5000,
    ]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('paymentAmount', '1000')
        ->set('paymentMethod', PaymentMethod::Cash->value)
        ->set('paymentDate', today()->format('Y-m-d'))
        ->call('addPayment')
        ->assertForbidden();
});

test('full payment sets enrollment status to paid', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'cohort_id' => $cohort->id,
        'amount_owed' => 1000,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('paymentAmount', '1000')
        ->set('paymentMethod', PaymentMethod::Cash->value)
        ->set('paymentDate', today()->format('Y-m-d'))
        ->call('addPayment')
        ->assertHasNoErrors();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Paid);
});

test('partial payment sets enrollment status to partial', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'cohort_id' => $cohort->id,
        'amount_owed' => 1000,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('paymentAmount', '500')
        ->set('paymentMethod', PaymentMethod::Cash->value)
        ->set('paymentDate', today()->format('Y-m-d'))
        ->call('addPayment')
        ->assertHasNoErrors();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Partial);
});

test('admin cannot set tuition when enrollment is fully paid', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'cohort_id' => $cohort->id,
        'amount_owed' => 1000,
        'status' => EnrollmentStatus::Paid,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('tuitionAmount', '9999')
        ->call('setTuition')
        ->assertForbidden();
});

test('admin cannot record payment when enrollment is fully paid', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'cohort_id' => $cohort->id,
        'amount_owed' => 1000,
        'status' => EnrollmentStatus::Paid,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('paymentAmount', '500')
        ->set('paymentMethod', PaymentMethod::Cash->value)
        ->set('paymentDate', today()->format('Y-m-d'))
        ->call('addPayment')
        ->assertForbidden();
});

test('finance page is accessible to admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('finance.index'))
        ->assertOk();
});

test('finance page is forbidden to staff', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('finance.index'))
        ->assertForbidden();
});
