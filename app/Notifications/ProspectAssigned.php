<?php

namespace App\Notifications;

use App\Models\Prospect;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProspectAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Prospect $prospect)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Prospect Assigned: {$this->prospect->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line('You have been assigned a new prospect.')
            ->line("**Name:** {$this->prospect->name}")
            ->line("**Email:** {$this->prospect->email}")
            ->line("**Phone:** {$this->prospect->phone}")
            ->line("**Status:** {$this->prospect->status->label()}")
            ->action('View Prospect', route('prospects.show', $this->prospect))
            ->line('Please follow up at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'prospect_id' => $this->prospect->id,
            'prospect_name' => $this->prospect->name,
            'prospect_email' => $this->prospect->email,
            'prospect_status' => $this->prospect->status->value,
            'message' => "You have been assigned a new prospect: {$this->prospect->name}",
        ];
    }
}
