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

        $sentBy = $this->getMetadata($headers, 'sent_by');
        $communicationType = $this->getMetadata($headers, 'communication_type');

        CommunicationLog::create([
            'prospect_id' => (int) $prospectId,
            'sent_by' => $sentBy ? (int) $sentBy : null,
            'channel' => CommunicationChannel::Email,
            'type' => $communicationType ? (CommunicationType::tryFrom($communicationType) ?? CommunicationType::Automated) : CommunicationType::Automated,
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
