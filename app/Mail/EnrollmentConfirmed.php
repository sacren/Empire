<?php

namespace App\Mail;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Models\CommunicationLog;
use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnrollmentConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Enrollment $enrollment)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Enrollment Confirmed — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.enrollment-confirmed',
            with: [
                'prospectName' => $this->enrollment->prospect->name,
                'cohortName' => $this->enrollment->cohort->name,
                'enrolledAt' => $this->enrollment->enrolled_at->format('F j, Y'),
                'tuitionAmount' => $this->enrollment->amount_owed !== null
                    ? '$'.number_format($this->enrollment->amount_owed, 2)
                    : 'To be determined',
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Log the sent email to communication_logs.
     */
    public function sent(EnrollmentConfirmed $mail): void
    {
        CommunicationLog::create([
            'prospect_id' => $this->enrollment->prospect_id,
            'sent_by' => null,
            'channel' => CommunicationChannel::Email,
            'type' => CommunicationType::Automated,
            'subject' => $this->envelope()->subject,
            'body' => 'Enrollment confirmation email sent for cohort: '.$this->enrollment->cohort->name,
            'sent_at' => now(),
        ]);
    }
}
