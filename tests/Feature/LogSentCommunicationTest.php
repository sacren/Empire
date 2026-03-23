<?php

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Listeners\LogSentCommunication;
use App\Models\CommunicationLog;
use App\Models\Prospect;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mime\Email;

function createMessageSentEvent(array $metadata = [], string $subject = 'Test Subject'): MessageSent
{
    $email = new Email;
    $email->subject($subject);
    $email->from('noreply@example.com');
    $email->to('student@example.com');
    $email->text('Test email content');

    foreach ($metadata as $key => $value) {
        $email->getHeaders()->add(new MetadataHeader($key, $value));
    }

    $sentMessage = new SentMessage(
        new \Symfony\Component\Mailer\SentMessage(
            $email,
            new \Symfony\Component\Mailer\Envelope(
                new \Symfony\Component\Mime\Address('noreply@example.com'),
                [new \Symfony\Component\Mime\Address('student@example.com')]
            )
        )
    );

    return new MessageSent($sentMessage);
}

test('listener creates communication log when metadata is present', function () {
    $prospect = Prospect::factory()->create();

    $event = createMessageSentEvent(
        metadata: [
            'prospect_id' => (string) $prospect->id,
            'log_body' => 'Test email body',
        ],
        subject: 'Payment Receipt — Empire Trade School',
    );

    $listener = new LogSentCommunication;
    $listener->handle($event);

    $log = CommunicationLog::where('prospect_id', $prospect->id)->first();

    expect($log)->not->toBeNull()
        ->and($log->channel)->toBe(CommunicationChannel::Email)
        ->and($log->type)->toBe(CommunicationType::Automated)
        ->and($log->sent_by)->toBeNull()
        ->and($log->subject)->toBe('Payment Receipt — Empire Trade School')
        ->and($log->body)->toBe('Test email body')
        ->and($log->sent_at)->not->toBeNull();
});

test('listener does nothing when metadata is absent', function () {
    $event = createMessageSentEvent();

    $listener = new LogSentCommunication;
    $listener->handle($event);

    expect(CommunicationLog::count())->toBe(0);
});

test('listener ignores emails without prospect_id metadata', function () {
    $event = createMessageSentEvent(
        metadata: ['some_other_key' => 'value'],
    );

    $listener = new LogSentCommunication;
    $listener->handle($event);

    expect(CommunicationLog::count())->toBe(0);
});
