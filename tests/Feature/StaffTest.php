<?php

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from staff index', function () {
    $this->get(route('staff.index'))->assertRedirect(route('login'));
});

test('admin can view staff index', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('staff.index'))
        ->assertOk();
});

test('staff cannot view staff index', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('staff.index'))
        ->assertForbidden();
});

test('admin can create a staff member', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::staff.create')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save');

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Staff)
        ->and($user->is_active)->toBeTrue();
});

test('staff cannot visit staff create page', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('staff.create'))
        ->assertForbidden();
});

test('admin can deactivate a staff member', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test('pages::staff.index')
        ->call('toggleStaff', $staff->id);

    expect($staff->fresh()->is_active)->toBeFalse();
});

test('password confirmation must match', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::staff.create')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'wrongpassword')
        ->call('save')
        ->assertHasErrors(['password']);
});
