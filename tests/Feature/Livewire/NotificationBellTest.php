<?php

use App\Models\Prospect;
use App\Models\User;
use App\Notifications\ProspectAssigned;
use Livewire\Livewire;

test('it renders for authenticated users', function () {
    $user = User::factory()->staff()->create();

    Livewire::actingAs($user)
        ->test('notification-bell')
        ->assertSuccessful();
});

test('it shows unread notification count', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create();

    $staff->notify(new ProspectAssigned($prospect));
    $staff->notify(new ProspectAssigned($prospect));

    Livewire::actingAs($staff)
        ->test('notification-bell')
        ->assertSee('2')
        ->assertViewHas('unreadCount', 2);
});

test('it shows empty state when no notifications', function () {
    $user = User::factory()->staff()->create();

    Livewire::actingAs($user)
        ->test('notification-bell')
        ->assertSee('No notifications')
        ->assertViewHas('unreadCount', 0);
});

test('it marks a single notification as read', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create();

    $staff->notify(new ProspectAssigned($prospect));
    $notification = $staff->notifications()->first();

    expect($notification->read_at)->toBeNull();

    Livewire::actingAs($staff)
        ->test('notification-bell')
        ->call('markAsRead', $notification->id)
        ->assertRedirect(route('prospects.show', $prospect));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('it marks all notifications as read', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create();

    $staff->notify(new ProspectAssigned($prospect));
    $staff->notify(new ProspectAssigned($prospect));

    expect($staff->unreadNotifications)->toHaveCount(2);

    Livewire::actingAs($staff)
        ->test('notification-bell')
        ->call('markAllAsRead')
        ->assertViewHas('unreadCount', 0);

    expect($staff->fresh()->unreadNotifications)->toHaveCount(0);
});

test('it only shows current user notifications', function () {
    $staffA = User::factory()->staff()->create();
    $staffB = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create();

    $staffA->notify(new ProspectAssigned($prospect));
    $staffB->notify(new ProspectAssigned($prospect));

    Livewire::actingAs($staffA)
        ->test('notification-bell')
        ->assertViewHas('notifications', fn ($notifications) => $notifications->count() === 1)
        ->assertViewHas('unreadCount', 1);
});

test('it redirects to prospect after marking as read', function () {
    $staff = User::factory()->staff()->create();
    $prospect = Prospect::factory()->create();

    $staff->notify(new ProspectAssigned($prospect));
    $notification = $staff->notifications()->first();

    Livewire::actingAs($staff)
        ->test('notification-bell')
        ->call('markAsRead', $notification->id)
        ->assertRedirect(route('prospects.show', $prospect));
});
