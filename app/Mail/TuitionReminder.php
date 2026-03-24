<?php

namespace App\Mail;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TuitionReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Enrollment $enrollment)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tuition Reminder — '.config('app.name'),
            metadata: [
                'prospect_id' => (string) $this->enrollment->prospect_id,
                'log_body' => 'Monthly tuition reminder — balance: $'.number_format($this->enrollment->balance(), 2),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tuition-reminder',
            with: [
                'prospectName' => $this->enrollment->prospect->name,
                'cohortName' => $this->enrollment->cohort->name,
                'totalOwed' => '$'.number_format($this->enrollment->amount_owed, 2),
                'totalPaid' => '$'.number_format($this->enrollment->totalPaid(), 2),
                'remainingBalance' => '$'.number_format($this->enrollment->balance(), 2),
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
}
