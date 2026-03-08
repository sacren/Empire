<?php

use App\Models\User;
use App\Services\RoundRobinAssignmentService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('returns null when no active staff members exist', function () {
    $service = app(RoundRobinAssignmentService::class);

    expect($service->nextStaffMember())->toBeNull();
});

test('assigns to the first staff member when none have been assigned', function () {
    $staff1 = User::factory()->staff()->create();
    $staff2 = User::factory()->staff()->create();

    $service = app(RoundRobinAssignmentService::class);
    $assigned = $service->nextStaffMember();

    expect($assigned->id)->toBe($staff1->id);
});

test('rotates through staff members in order', function () {
    $staff1 = User::factory()->staff()->create();
    $staff2 = User::factory()->staff()->create();
    $staff3 = User::factory()->staff()->create();

    $service = app(RoundRobinAssignmentService::class);

    expect($service->nextStaffMember()->id)->toBe($staff1->id);
    expect($service->nextStaffMember()->id)->toBe($staff2->id);
    expect($service->nextStaffMember()->id)->toBe($staff3->id);
    expect($service->nextStaffMember()->id)->toBe($staff1->id);
});

test('skips inactive staff members', function () {
    $active = User::factory()->staff()->create();
    $inactive = User::factory()->staff()->inactive()->create();

    $service = app(RoundRobinAssignmentService::class);

    expect($service->nextStaffMember()->id)->toBe($active->id);
    expect($service->nextStaffMember()->id)->toBe($active->id);
});

test('returns null when all staff members are inactive', function () {
    User::factory()->staff()->inactive()->create();

    $service = app(RoundRobinAssignmentService::class);

    expect($service->nextStaffMember())->toBeNull();
});
