<?php

use App\Models\Cohort;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Record Attendance')] class extends Component {
    public Cohort $cohort;

    public function mount(Cohort $cohort): void
    {
        $this->authorize('attendance', $cohort);
        $this->cohort = $cohort->load('enrollments.prospect');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Record Attendance') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">{{ $cohort->name }}</flux:text>
        </div>
        <flux:button :href="route('cohorts.index')" wire:navigate>{{ __('Back to Cohorts') }}</flux:button>
    </div>

    <flux:text class="text-zinc-500">{{ __('Attendance form coming in Step 3.') }}</flux:text>
</div>
