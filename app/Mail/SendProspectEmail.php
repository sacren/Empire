<?php

namespace App\Mail;

use App\Enums\CommunicationType;
use App\Models\Prospect;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendProspectEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Prospect $prospect,
        public string $emailSubject,
        public string $emailBody,
        public int $sentById,
        public CommunicationType $communicationType = CommunicationType::Manual,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject.' — '.config('app.name'),
            metadata: [
                'prospect_id' => (string) $this->prospect->id,
                'log_body' => $this->emailBody,
                'sent_by' => (string) $this->sentById,
                'communication_type' => $this->communicationType->value,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.send-prospect-email',
            with: [
                'body' => $this->emailBody,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
