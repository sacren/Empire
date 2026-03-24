<?php

use App\Enums\ProspectStatus;
use App\Models\Prospect;
use App\Models\User;
use App\Notifications\ProspectAssigned;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;

test('it sends notification when prospect is assigned to staff', function () {
    Notification::fake();

    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => null]);

    $prospect->update(['assigned_to' => $staff->id]);

    Notification::assertSentTo($staff, ProspectAssigned::class);
});

test('it does not send notification when prospect is unassigned', function () {
    Notification::fake();

    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $staff->id]);

    Notification::fake(); // reset after create

    $prospect->update(['assigned_to' => null]);

    Notification::assertNothingSent();
});

test('it does not send notification when other fields change', function () {
    Notification::fake();

    $prospect = Prospect::factory()->create();

    $prospect->update(['status' => ProspectStatus::Contacted]);

    Notification::assertNothingSent();
});

test('it sends notification to new assignee on reassignment', function () {
    Notification::fake();

    $staffA = User::factory()->staff()->create();
    $staffB = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $staffA->id]);

    Notification::fake(); // reset after create

    $prospect->update(['assigned_to' => $staffB->id]);

    Notification::assertSentTo($staffB, ProspectAssigned::class);
    Notification::assertNotSentTo($staffA, ProspectAssigned::class);
});

test('it includes correct data in database channel', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::New,
    ]);

    $notification = new ProspectAssigned($prospect);
    $data = $notification->toArray($staff);

    expect($data)
        ->prospect_id->toBe($prospect->id)
        ->prospect_name->toBe($prospect->name)
        ->prospect_email->toBe($prospect->email)
        ->prospect_status->toBe(ProspectStatus::New->value)
        ->message->toContain($prospect->name);
});

test('it includes correct content in mail channel', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create([
        'status' => ProspectStatus::New,
    ]);

    $notification = new ProspectAssigned($prospect);
    $mail = $notification->toMail($staff);

    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->toContain($prospect->name);
    expect($mail->actionUrl)->toBe(route('prospects.show', $prospect));

    $introLines = implode(' ', $mail->introLines);
    expect($introLines)
        ->toContain($prospect->name)
        ->toContain($prospect->email)
        ->toContain($prospect->phone)
        ->toContain($prospect->status->label());
});
