<?php

use App\Enums\CommunicationType;
use App\Enums\ProspectStatus;
use App\Mail\SendProspectEmail;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('admin can access message page', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->assertSuccessful();
});

test('staff cannot access message page', function () {
    $staff = User::factory()->staff()->create();
    $cohort = Cohort::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->assertForbidden();
});

test('it shows recipient count', function () {
    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    $enrolled = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $graduated = Prospect::factory()->create(['status' => ProspectStatus::Graduated]);

    Enrollment::factory()->create(['prospect_id' => $enrolled->id, 'cohort_id' => $cohort->id]);
    Enrollment::factory()->create(['prospect_id' => $graduated->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->assertSee('Will be sent to 2 student(s)');
});

test('it sends email to all enrolled students', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    $enrolled = Prospect::factory()->create(['status' => ProspectStatus::Enrolled, 'email' => 'enrolled@example.com']);
    $graduated = Prospect::factory()->create(['status' => ProspectStatus::Graduated, 'email' => 'graduated@example.com']);

    Enrollment::factory()->create(['prospect_id' => $enrolled->id, 'cohort_id' => $cohort->id]);
    Enrollment::factory()->create(['prospect_id' => $graduated->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->set('emailSubject', 'Class Update')
        ->set('emailBody', 'Important announcement.')
        ->call('sendAnnouncement')
        ->assertHasNoErrors();

    Mail::assertQueued(SendProspectEmail::class, 2);

    Mail::assertQueued(SendProspectEmail::class, function (SendProspectEmail $mail) {
        return $mail->hasTo('enrolled@example.com');
    });

    Mail::assertQueued(SendProspectEmail::class, function (SendProspectEmail $mail) {
        return $mail->hasTo('graduated@example.com');
    });
});

test('it does not send to disqualified or abandoned prospects', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    $enrolled = Prospect::factory()->create(['status' => ProspectStatus::Enrolled, 'email' => 'enrolled@example.com']);
    $disqualified = Prospect::factory()->create(['status' => ProspectStatus::Disqualified]);
    $abandoned = Prospect::factory()->create(['status' => ProspectStatus::Abandoned]);

    Enrollment::factory()->create(['prospect_id' => $enrolled->id, 'cohort_id' => $cohort->id]);
    Enrollment::factory()->create(['prospect_id' => $disqualified->id, 'cohort_id' => $cohort->id]);
    Enrollment::factory()->create(['prospect_id' => $abandoned->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->set('emailSubject', 'Class Update')
        ->set('emailBody', 'Important announcement.')
        ->call('sendAnnouncement')
        ->assertHasNoErrors();

    Mail::assertQueued(SendProspectEmail::class, 1);

    Mail::assertQueued(SendProspectEmail::class, function (SendProspectEmail $mail) {
        return $mail->hasTo('enrolled@example.com');
    });
});

test('it validates subject and body are required', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->set('emailSubject', '')
        ->set('emailBody', '')
        ->call('sendAnnouncement')
        ->assertHasErrors(['emailSubject' => 'required', 'emailBody' => 'required']);

    Mail::assertNothingQueued();
});

test('it validates subject max length', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->set('emailSubject', str_repeat('a', 256))
        ->set('emailBody', 'Valid body.')
        ->call('sendAnnouncement')
        ->assertHasErrors(['emailSubject' => 'max']);

    Mail::assertNothingQueued();
});

test('it resets form after sending', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->set('emailSubject', 'Test subject')
        ->set('emailBody', 'Test body')
        ->call('sendAnnouncement')
        ->assertSet('emailSubject', '')
        ->assertSet('emailBody', '');
});

test('it shows success message with count', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    $prospect1 = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    $prospect2 = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);

    Enrollment::factory()->create(['prospect_id' => $prospect1->id, 'cohort_id' => $cohort->id]);
    Enrollment::factory()->create(['prospect_id' => $prospect2->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->set('emailSubject', 'Update')
        ->set('emailBody', 'Body text')
        ->call('sendAnnouncement')
        ->assertSee('Announcement sent to 2 student(s).');
});

test('it passes Bulk communication type to mailable', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    $prospect = Prospect::factory()->create(['status' => ProspectStatus::Enrolled]);
    Enrollment::factory()->create(['prospect_id' => $prospect->id, 'cohort_id' => $cohort->id]);

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->set('emailSubject', 'Announcement')
        ->set('emailBody', 'Body text')
        ->call('sendAnnouncement');

    Mail::assertQueued(SendProspectEmail::class, function (SendProspectEmail $mail) {
        return $mail->communicationType === CommunicationType::Bulk;
    });
});

test('it handles empty cohort gracefully', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $cohort = Cohort::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::cohorts.message', ['cohort' => $cohort])
        ->set('emailSubject', 'Update')
        ->set('emailBody', 'Body text')
        ->call('sendAnnouncement')
        ->assertSee('No eligible students in this cohort.')
        ->assertHasNoErrors();

    Mail::assertNothingQueued();
});
