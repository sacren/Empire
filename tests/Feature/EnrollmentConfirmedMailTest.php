<?php

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Enums\ProspectStatus;
use App\Mail\EnrollmentConfirmed;
use App\Models\Cohort;
use App\Models\CommunicationLog;
use App\Models\Enrollment;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('enrollment confirmed mailable contains correct content', function () {
    $prospect = Prospect::factory()->create(['name' => 'Jane Doe']);
    $cohort = Cohort::factory()->create(['name' => 'Spring 2026']);
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'cohort_id' => $cohort->id,
        'enrolled_at' => now(),
        'amount_owed' => 5000.00,
    ]);

    $mailable = new EnrollmentConfirmed($enrollment);

    $mailable->assertHasSubject('Enrollment Confirmed — '.config('app.name'));
    $mailable->assertSeeInHtml('Jane Doe');
    $mailable->assertSeeInHtml('Spring 2026');
    $mailable->assertSeeInHtml('$5,000.00');
});

test('enrollment confirmed mailable shows "to be determined" when tuition is not set', function () {
    $enrollment = Enrollment::factory()->create([
        'amount_owed' => null,
        'enrolled_at' => now(),
    ]);

    $mailable = new EnrollmentConfirmed($enrollment);

    $mailable->assertSeeInHtml('To be determined');
});

test('enrollment confirmed mailable implements ShouldQueue', function () {
    expect(EnrollmentConfirmed::class)
        ->toImplement(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

test('enrolling a prospect queues the enrollment confirmed email', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Qualified,
        'email' => 'student@example.com',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll')
        ->assertHasNoErrors();

    Mail::assertQueued(EnrollmentConfirmed::class, function (EnrollmentConfirmed $mail) use ($prospect) {
        return $mail->hasTo('student@example.com')
            && $mail->enrollment->prospect_id === $prospect->id;
    });
});

test('enrollment confirmed email is sent to the correct prospect email', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();
    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::Qualified,
        'email' => 'specific@example.com',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('enrollCohortId', (string) $cohort->id)
        ->call('enroll');

    Mail::assertQueued(EnrollmentConfirmed::class, 'specific@example.com');
});

test('enrollment confirmed email creates a communication log', function () {
    $prospect = Prospect::factory()->create();
    $cohort = Cohort::factory()->create(['name' => 'Fall 2026']);
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'cohort_id' => $cohort->id,
        'enrolled_at' => now(),
    ]);

    $mailable = new EnrollmentConfirmed($enrollment);
    $mailable->sent($mailable);

    $log = CommunicationLog::where('prospect_id', $prospect->id)->first();

    expect($log)->not->toBeNull()
        ->and($log->channel)->toBe(CommunicationChannel::Email)
        ->and($log->type)->toBe(CommunicationType::Automated)
        ->and($log->sent_by)->toBeNull()
        ->and($log->subject)->toContain('Enrollment Confirmed')
        ->and($log->body)->toContain('Fall 2026');
});
