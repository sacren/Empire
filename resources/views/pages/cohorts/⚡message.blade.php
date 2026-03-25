<?php

use App\Enums\CommunicationType;
use App\Enums\ProspectStatus;
use App\Mail\SendProspectEmail;
use App\Models\Cohort;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Send Announcement')] class extends Component {
    public Cohort $cohort;
    public string $emailSubject = '';
    public string $emailBody = '';

    public function mount(Cohort $cohort): void
    {
        $this->authorize('message', $cohort);
        $this->cohort = $cohort;
    }

    #[Computed]
    public function recipients(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->cohort->enrollments()
            ->whereHas('prospect', fn ($q) => $q->whereIn('status', [
                ProspectStatus::Enrolled,
                ProspectStatus::Graduated,
            ]))
            ->with('prospect')
            ->get();
    }

    public function sendAnnouncement(): void
    {
        $this->authorize('message', $this->cohort);

        $this->validate([
            'emailSubject' => ['required', 'string', 'max:255'],
            'emailBody' => ['required', 'string', 'max:10000'],
        ]);

        $recipients = $this->recipients;

        if ($recipients->isEmpty()) {
            session()->flash('noRecipients', __('No eligible students in this cohort.'));

            return;
        }

        foreach ($recipients as $enrollment) {
            Mail::to($enrollment->prospect->email)->send(new SendProspectEmail(
                $enrollment->prospect,
                $this->emailSubject,
                $this->emailBody,
                auth()->id(),
                CommunicationType::Bulk,
            ));
        }

        $count = $recipients->count();

        $this->emailSubject = '';
        $this->emailBody = '';

        session()->flash('announcementSent', __("Announcement sent to {$count} student(s)."));
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button icon="arrow-left" :href="route('cohorts.index')" wire:navigate variant="ghost" />
        <div>
            <flux:heading size="xl">{{ __('Send Announcement') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">{{ $cohort->name }}</flux:text>
        </div>
    </div>

    @if (session('announcementSent'))
        <div class="mb-4">
            <flux:callout variant="success">{{ session('announcementSent') }}</flux:callout>
        </div>
    @endif

    @if (session('noRecipients'))
        <div class="mb-4">
            <flux:callout variant="warning">{{ session('noRecipients') }}</flux:callout>
        </div>
    @endif

    @if ($this->recipients->isEmpty())
        <flux:text class="text-zinc-500">{{ __('No eligible students in this cohort.') }}</flux:text>
    @else
        <div class="mb-4">
            <flux:badge color="blue" size="lg">{{ __('Will be sent to :count student(s)', ['count' => $this->recipients->count()]) }}</flux:badge>
        </div>

        <div class="max-w-lg">
            <form wire:submit="sendAnnouncement" class="flex flex-col gap-5">
                <flux:field>
                    <flux:label>{{ __('Subject') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                    <flux:input wire:model="emailSubject" placeholder="{{ __('Email subject...') }}" />
                    <flux:error name="emailSubject" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Body') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                    <flux:textarea wire:model="emailBody" rows="6" placeholder="{{ __('Write your message...') }}" />
                    <flux:error name="emailBody" />
                </flux:field>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled">{{ __('Send Announcement') }}</flux:button>
                </div>
            </form>
        </div>
    @endif
</div>
