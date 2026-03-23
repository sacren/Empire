<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt — '.config('app.name'),
            metadata: [
                'prospect_id' => (string) $this->payment->enrollment->prospect_id,
                'log_body' => 'Payment receipt for $'.number_format($this->payment->amount, 2).' via '.$this->payment->method->label(),
            ],
        );
    }

    public function content(): Content
    {
        $enrollment = $this->payment->enrollment;

        return new Content(
            markdown: 'mail.payment-received',
            with: [
                'prospectName' => $enrollment->prospect->name,
                'amount' => '$'.number_format($this->payment->amount, 2),
                'method' => $this->payment->method->label(),
                'paidAt' => $this->payment->paid_at->format('F j, Y'),
                'remainingBalance' => '$'.number_format($enrollment->balance(), 2),
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
