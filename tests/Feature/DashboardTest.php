<?php

use App\Models\Prospect;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('admin can view the dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk();
});

test('staff can view the dashboard', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk();
});

test('dashboard shows correct unassigned prospect count', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();

    Prospect::factory(3)->create(['assigned_to' => null]);
    Prospect::factory(2)->create(['assigned_to' => $staff->id]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('3');
});
