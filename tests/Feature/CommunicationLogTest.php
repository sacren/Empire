<?php

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Models\CommunicationLog;
use App\Models\Prospect;
use App\Models\User;

test('communication log belongs to a prospect', function () {
    $prospect = Prospect::factory()->create();
    $log = CommunicationLog::factory()->create(['prospect_id' => $prospect->id]);

    expect($log->prospect->id)->toBe($prospect->id);
});

test('communication log belongs to a sender', function () {
    $user = User::factory()->create();
    $log = CommunicationLog::factory()->create(['sent_by' => $user->id]);

    expect($log->sentBy->id)->toBe($user->id);
});

test('automated communication log has no sender', function () {
    $log = CommunicationLog::factory()->automated()->create();

    expect($log->sent_by)->toBeNull()
        ->and($log->type)->toBe(CommunicationType::Automated);
});

test('prospect has communication logs relationship', function () {
    $prospect = Prospect::factory()->create();
    CommunicationLog::factory()->count(3)->create(['prospect_id' => $prospect->id]);

    expect($prospect->communicationLogs)->toHaveCount(3);
});

test('communication log casts channel and type to enums', function () {
    $log = CommunicationLog::factory()->create([
        'channel' => CommunicationChannel::Email->value,
        'type' => CommunicationType::Manual->value,
    ]);

    $log->refresh();

    expect($log->channel)->toBe(CommunicationChannel::Email)
        ->and($log->type)->toBe(CommunicationType::Manual);
});

test('communication log factory states work correctly', function () {
    $manual = CommunicationLog::factory()->manual()->create();
    $bulk = CommunicationLog::factory()->bulk()->create();

    expect($manual->type)->toBe(CommunicationType::Manual)
        ->and($bulk->type)->toBe(CommunicationType::Bulk);
});

test('deleting a prospect cascades to communication logs', function () {
    $prospect = Prospect::factory()->create();
    CommunicationLog::factory()->count(2)->create(['prospect_id' => $prospect->id]);

    $prospect->delete();

    expect(CommunicationLog::count())->toBe(0);
});
