<?php

namespace App\Listeners;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Models\CommunicationLog;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mailer\Header\MetadataHeader;

class LogSentCommunication
{
    public function handle(MessageSent $event): void
    {
        $headers = $event->message->getHeaders();

        $prospectId = $this->getMetadata($headers, 'prospect_id');

        if (! $prospectId) {
            return;
        }

        CommunicationLog::create([
            'prospect_id' => (int) $prospectId,
            'sent_by' => null,
            'channel' => CommunicationChannel::Email,
            'type' => CommunicationType::Automated,
            'subject' => $event->message->getSubject(),
            'body' => $this->getMetadata($headers, 'log_body') ?? '',
            'sent_at' => now(),
        ]);
    }

    private function getMetadata(object $headers, string $key): ?string
    {
        foreach ($headers->all() as $header) {
            if ($header instanceof MetadataHeader && $header->getKey() === $key) {
                return $header->getValue();
            }
        }

        return null;
    }
}
