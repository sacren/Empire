<?php

use App\Enums\EnrollmentStatus;
use App\Enums\ProspectStatus;
use App\Mail\TuitionReminder;
use App\Models\Enrollment;
use App\Models\Prospect;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

test('sends reminder to enrollment with partial status', function () {
    Mail::fake();

    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Enrolled,
        'email' => 'student@example.com',
    ]);
    Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 5000.00,
        'status' => EnrollmentStatus::Partial,
    ]);

    $this->artisan('app:send-tuition-reminders')
        ->expectsOutputToContain('Sent 1 tuition reminder(s).')
        ->assertSuccessful();

    Mail::assertQueued(TuitionReminder::class, function (TuitionReminder $mail) {
        return $mail->hasTo('student@example.com');
    });
});

test('sends reminder to enrollment with pending status and amount owed set', function () {
    Mail::fake();

    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Enrolled,
    ]);
    Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 3000.00,
        'status' => EnrollmentStatus::Pending,
    ]);

    $this->artisan('app:send-tuition-reminders')->assertSuccessful();

    Mail::assertQueued(TuitionReminder::class, 1);
});

test('sends reminder to graduated prospect with outstanding balance', function () {
    Mail::fake();

    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Graduated,
    ]);
    Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 4000.00,
        'status' => EnrollmentStatus::Partial,
    ]);

    $this->artisan('app:send-tuition-reminders')->assertSuccessful();

    Mail::assertQueued(TuitionReminder::class, 1);
});

test('skips fully paid enrollment', function () {
    Mail::fake();

    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Enrolled,
    ]);
    Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 5000.00,
        'status' => EnrollmentStatus::Paid,
    ]);

    $this->artisan('app:send-tuition-reminders')
        ->expectsOutputToContain('Sent 0 tuition reminder(s).')
        ->assertSuccessful();

    Mail::assertNothingQueued();
});

test('skips enrollment with null amount owed', function () {
    Mail::fake();

    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Enrolled,
    ]);
    Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => null,
        'status' => EnrollmentStatus::Pending,
    ]);

    $this->artisan('app:send-tuition-reminders')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('skips enrollment with zero amount owed', function () {
    Mail::fake();

    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Enrolled,
    ]);
    Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 0.00,
        'status' => EnrollmentStatus::Pending,
    ]);

    $this->artisan('app:send-tuition-reminders')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('skips disqualified and abandoned prospect enrollments', function () {
    Mail::fake();

    foreach ([ProspectStatus::Disqualified, ProspectStatus::Abandoned] as $status) {
        $prospect = Prospect::factory()->create(['status' => $status]);
        Enrollment::factory()->create([
            'prospect_id' => $prospect->id,
            'amount_owed' => 5000.00,
            'status' => EnrollmentStatus::Partial,
        ]);
    }

    $this->artisan('app:send-tuition-reminders')
        ->expectsOutputToContain('Sent 0 tuition reminder(s).')
        ->assertSuccessful();

    Mail::assertNothingQueued();
});

test('tuition reminder mailable contains correct content', function () {
    $prospect = Prospect::factory()->create(['name' => 'Jane Doe']);
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 6000.00,
        'status' => EnrollmentStatus::Partial,
    ]);

    Mail::fake();

    $mailable = new TuitionReminder($enrollment);

    $mailable->assertHasSubject('Tuition Reminder — '.config('app.name'));
    $mailable->assertSeeInHtml('Jane Doe');
    $mailable->assertSeeInHtml('$6,000.00');
    $mailable->assertSeeInHtml($enrollment->cohort->name);
});

test('tuition reminder mailable has prospect_id metadata', function () {
    $prospect = Prospect::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 4000.00,
        'status' => EnrollmentStatus::Partial,
    ]);

    Mail::fake();

    $mailable = new TuitionReminder($enrollment);

    expect($mailable->envelope()->metadata)
        ->toHaveKey('prospect_id', (string) $prospect->id)
        ->toHaveKey('log_body');

    expect($mailable->envelope()->metadata['log_body'])
        ->toContain('$4,000.00');
});

test('tuition reminder mailable implements ShouldQueue', function () {
    expect(TuitionReminder::class)
        ->toImplement(ShouldQueue::class);
});
