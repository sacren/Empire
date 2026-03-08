<?php

use App\Enums\ActivityAction;
use App\Enums\ActivityType;
use App\Enums\ProspectStatus;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\User;
use Livewire\Livewire;

// Index page
test('guests are redirected from prospects index', function () {
    $this->get(route('prospects.index'))->assertRedirect(route('login'));
});

test('admin can view all prospects', function () {
    $admin = User::factory()->admin()->create();
    Prospect::factory(3)->create();

    $this->actingAs($admin)
        ->get(route('prospects.index'))
        ->assertOk();
});

test('staff can view prospects index', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('prospects.index'))
        ->assertOk();
});

// Show page
test('admin can view any prospect', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create();

    $this->actingAs($admin)
        ->get(route('prospects.show', $prospect))
        ->assertOk();
});

test('staff can view their own assigned prospect', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->assignedTo($staff)->create();

    $this->actingAs($staff)
        ->get(route('prospects.show', $prospect))
        ->assertOk();
});

test('staff cannot view a prospect assigned to someone else', function () {
    $staff = User::factory()->staff()->create();
    $other = User::factory()->staff()->create();
    $prospect = Prospect::factory()->assignedTo($other)->create();

    $this->actingAs($staff)
        ->get(route('prospects.show', $prospect))
        ->assertForbidden();
});

// Create page
test('admin can visit create prospect page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('prospects.create'))
        ->assertOk();
});

test('staff can create a prospect', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('prospects.create'))
        ->assertOk();
});

test('staff-entered prospect saves correctly and logs activity', function () {
    $staff = User::factory()->staff()->create();

    Livewire::actingAs($staff)
        ->test('pages::prospects.create')
        ->set('name', 'Alice Johnson')
        ->set('email', 'alice@example.com')
        ->set('phone', '555-1234')
        ->set('preferred_contact_method', 'phone')
        ->set('best_time_to_contact', 'morning')
        ->call('save');

    $prospect = Prospect::where('email', 'alice@example.com')->first();

    expect($prospect)->not->toBeNull()
        ->and($prospect->name)->toBe('Alice Johnson')
        ->and($prospect->status)->toBe(ProspectStatus::New);

    expect(ProspectActivity::where('prospect_id', $prospect->id)->count())->toBe(1);
});

// Status update / Observer
test('updating prospect status creates automatic activity log', function () {
    $prospect = Prospect::factory()->create(['status' => ProspectStatus::New]);

    $prospect->update(['status' => ProspectStatus::Contacted]);

    $activity = ProspectActivity::where('prospect_id', $prospect->id)
        ->where('action', ActivityAction::StatusChange->value)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->type)->toBe(ActivityType::Automatic);
});

test('updating prospect assigned_to creates automatic activity log', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => null]);

    $prospect->update(['assigned_to' => $staff->id]);

    $activity = ProspectActivity::where('prospect_id', $prospect->id)
        ->where('action', ActivityAction::AssignmentChange->value)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->type)->toBe(ActivityType::Automatic);
});

// Delete
test('admin can delete a prospect', function () {
    $admin = User::factory()->admin()->create();
    $prospect = Prospect::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->call('delete');

    expect(Prospect::find($prospect->id))->toBeNull();
});

test('staff cannot delete a prospect', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->assignedTo($staff)->create();

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->call('delete')
        ->assertForbidden();
});

// Reassignment
test('admin can reassign a prospect', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create(['assigned_to' => null]);

    Livewire::actingAs($admin)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('newAssignedTo', (string) $staff->id)
        ->call('updateAssignment');

    expect($prospect->fresh()->assigned_to)->toBe($staff->id);
});

test('staff cannot reassign a prospect', function () {
    $staff = User::factory()->staff()->create();
    $other = User::factory()->staff()->create();
    $prospect = Prospect::factory()->assignedTo($staff)->create();

    Livewire::actingAs($staff)
        ->test('pages::prospects.show', ['prospect' => $prospect])
        ->set('newAssignedTo', (string) $other->id)
        ->call('updateAssignment')
        ->assertForbidden();
});
