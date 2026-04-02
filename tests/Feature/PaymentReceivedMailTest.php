<?php

use App\Enums\PaymentMethod;
use App\Mail\PaymentReceived;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Prospect;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

test('payment received mailable contains correct content', function () {
    $prospect = Prospect::factory()->create(['name' => 'John Smith']);
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 5000.00,
    ]);
    $payment = Payment::factory()->create([
        'enrollment_id' => $enrollment->id,
        'amount' => 1500.00,
        'method' => PaymentMethod::Card,
        'paid_at' => '2026-03-15',
    ]);

    Mail::fake();

    $mailable = new PaymentReceived($payment);

    $mailable->assertHasSubject('Payment Receipt — '.config('app.name'));
    $mailable->assertSeeInHtml('John Smith');
    $mailable->assertSeeInHtml('$1,500.00');
    $mailable->assertSeeInHtml('Card');
    $mailable->assertSeeInHtml('March 15, 2026');
    $mailable->assertSeeInHtml('$3,500.00');
});

test('payment received mailable implements ShouldQueue', function () {
    expect(PaymentReceived::class)
        ->toImplement(ShouldQueue::class);
});

test('creating a payment queues the payment received email', function () {
    Mail::fake();

    $prospect = Prospect::factory()->create(['email' => 'student@example.com']);
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 5000.00,
    ]);

    Payment::factory()->create([
        'enrollment_id' => $enrollment->id,
        'amount' => 1000.00,
    ]);

    Mail::assertQueued(PaymentReceived::class, function (PaymentReceived $mail) {
        return $mail->hasTo('student@example.com');
    });
});

test('payment received mailable includes prospect_id metadata', function () {
    $prospect = Prospect::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'prospect_id' => $prospect->id,
        'amount_owed' => 5000.00,
    ]);
    $payment = Payment::factory()->create([
        'enrollment_id' => $enrollment->id,
        'amount' => 2000.00,
        'method' => PaymentMethod::Check,
    ]);

    Mail::fake();

    $mailable = new PaymentReceived($payment);

    expect($mailable->envelope()->metadata)
        ->toHaveKey('prospect_id', (string) $prospect->id)
        ->toHaveKey('log_body');

    expect($mailable->envelope()->metadata['log_body'])
        ->toContain('$2,000.00')
        ->toContain('Check');
});
