<?php

use App\Enums\CommunicationType;
use App\Mail\SendProspectEmail;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('it sends email to prospect', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create([
        'email' => 'student@example.com',
        'assigned_to' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('emailSubject', 'Welcome aboard')
        ->set('emailBody', 'We are happy to have you.')
        ->call('sendEmail')
        ->assertHasNoErrors();

    Mail::assertQueued(SendProspectEmail::class, function (SendProspectEmail $mail) {
        return $mail->hasTo('student@example.com')
            && $mail->emailSubject === 'Welcome aboard'
            && $mail->emailBody === 'We are happy to have you.';
    });
});

test('it validates subject and body are required', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $admin->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('emailSubject', '')
        ->set('emailBody', '')
        ->call('sendEmail')
        ->assertHasErrors(['emailSubject' => 'required', 'emailBody' => 'required']);

    Mail::assertNothingQueued();
});

test('it validates subject max length', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $admin->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('emailSubject', str_repeat('a', 256))
        ->set('emailBody', 'Valid body.')
        ->call('sendEmail')
        ->assertHasErrors(['emailSubject' => 'max']);

    Mail::assertNothingQueued();
});

test('it resets form after sending', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $admin->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('emailSubject', 'Test subject')
        ->set('emailBody', 'Test body')
        ->call('sendEmail')
        ->assertSet('emailSubject', '')
        ->assertSet('emailBody', '');
});

test('it includes correct content in email', function () {
    $prospect = Prospect::factory()->create(['name' => 'Jane Doe']);

    $mailable = new SendProspectEmail($prospect, 'Follow Up', "Hello Jane,\nWe wanted to check in.", 1);

    $mailable->assertSeeInHtml('Hello Jane,');
    $mailable->assertSeeInHtml('We wanted to check in.');
});

test('it appends app name to subject', function () {
    $prospect = Prospect::factory()->create();

    $mailable = new SendProspectEmail($prospect, 'Follow Up', 'Body text', 1);

    $mailable->assertHasSubject('Follow Up — '.config('app.name'));
});

test('it implements ShouldQueue', function () {
    expect(SendProspectEmail::class)
        ->toImplement(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

test('it requires authorization', function () {
    Mail::fake();

    $staff = User::factory()->staff()->create();
    $otherStaff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $otherStaff->id]);

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->assertForbidden();
});

test('it includes correct metadata in envelope', function () {
    $prospect = Prospect::factory()->create();
    $userId = User::factory()->admin()->create()->id;

    $mailable = new SendProspectEmail($prospect, 'Check In', 'How are you?', $userId);

    $metadata = $mailable->envelope()->metadata;

    expect($metadata)
        ->toHaveKey('prospect_id', (string) $prospect->id)
        ->toHaveKey('log_body', 'How are you?')
        ->toHaveKey('sent_by', (string) $userId)
        ->toHaveKey('communication_type', CommunicationType::Manual->value);
});
