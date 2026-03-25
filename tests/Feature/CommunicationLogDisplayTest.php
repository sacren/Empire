<?php

use App\Models\CommunicationLog;
use App\Models\Prospect;
use App\Models\User;
use Livewire\Livewire;

test('it shows communication logs on prospect show page', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $admin->id]);

    $log = CommunicationLog::factory()->manual()->create([
        'prospect_id' => $prospect->id,
        'sent_by' => $admin->id,
        'subject' => 'Welcome to the program',
        'sent_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->assertSee('Welcome to the program')
        ->assertSee('Manual');
});

test('it shows System for automated logs without sent_by', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $admin->id]);

    CommunicationLog::factory()->automated()->create([
        'prospect_id' => $prospect->id,
        'subject' => 'Enrollment Confirmed',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->assertSee('System')
        ->assertSee('Automated');
});

test('it shows sent by name for manual logs', function () {
    $admin = User::factory()->admin()->create(['name' => 'Jane Admin']);
    $prospect = Prospect::factory()->create(['assigned_to' => $admin->id]);

    CommunicationLog::factory()->manual()->create([
        'prospect_id' => $prospect->id,
        'sent_by' => $admin->id,
        'subject' => 'Follow up email',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->assertSee('Jane Admin');
});

test('it shows communication body when expanded', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $admin->id]);

    CommunicationLog::factory()->manual()->create([
        'prospect_id' => $prospect->id,
        'sent_by' => $admin->id,
        'body' => 'This is the full body of the communication.',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->assertSee('This is the full body of the communication.');
});

test('it shows logs in newest first order', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $admin->id]);

    CommunicationLog::factory()->manual()->create([
        'prospect_id' => $prospect->id,
        'sent_by' => $admin->id,
        'subject' => 'Older Message',
        'sent_at' => now()->subDay(),
        'created_at' => now()->subDay(),
    ]);

    CommunicationLog::factory()->manual()->create([
        'prospect_id' => $prospect->id,
        'sent_by' => $admin->id,
        'subject' => 'Newer Message',
        'sent_at' => now(),
        'created_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->assertSeeInOrder(['Newer Message', 'Older Message']);
});

test('it shows empty state when no logs exist', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => $admin->id]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->assertSee('No communications recorded yet.');
});
