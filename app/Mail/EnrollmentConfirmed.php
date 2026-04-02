<?php

namespace App\Mail;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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
            metadata: [
                'prospect_id' => (string) $this->enrollment->prospect_id,
                'log_body' => 'Enrollment confirmation email sent for cohort: '.$this->enrollment->cohort->name,
            ],
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
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
