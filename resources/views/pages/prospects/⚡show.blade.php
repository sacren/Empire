<?php

use App\Enums\ActivityAction;
use App\Enums\ActivityType;
use App\Enums\ProspectStatus;
use App\Enums\UserRole;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Prospect')] class extends Component {
    public Prospect $prospect;

    public string $activityAction = '';
    public string $activityNotes = '';
    public string $newStatus = '';
    public string $newAssignedTo = '';

    public function mount(Prospect $prospect): void
    {
        $this->authorize('view', $prospect);
        $this->prospect = $prospect->load(['cohort', 'assignedTo', 'activities.performedBy']);
        $this->newStatus = $prospect->status->value;
        $this->newAssignedTo = (string) ($prospect->assigned_to ?? '');
    }

    #[Computed]
    public function staffMembers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()->where('role', UserRole::Staff->value)->orderBy('name')->get();
    }

    public function logActivity(): void
    {
        $this->validate([
            'activityAction' => ['required', 'string'],
            'activityNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        ProspectActivity::create([
            'prospect_id' => $this->prospect->id,
            'type' => ActivityType::Manual,
            'action' => $this->activityAction,
            'notes' => $this->activityNotes,
            'performed_by' => auth()->id(),
        ]);

        $this->activityAction = '';
        $this->activityNotes = '';
        $this->prospect->refresh()->load('activities.performedBy');
    }

    public function updateStatus(): void
    {
        $this->authorize('update', $this->prospect);
        $this->validate(['newStatus' => ['required', 'string']]);

        $this->prospect->update(['status' => $this->newStatus]);
        $this->prospect->refresh()->load('activities.performedBy');
    }

    public function updateAssignment(): void
    {
        $this->authorize('reassign', $this->prospect);
        $this->validate(['newAssignedTo' => ['nullable', 'exists:users,id']]);

        $this->prospect->update(['assigned_to' => $this->newAssignedTo ?: null]);
        $this->prospect->refresh()->load(['assignedTo', 'activities.performedBy']);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->prospect);
        $this->prospect->delete();
        $this->redirectRoute('prospects.index', navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <flux:button icon="arrow-left" :href="route('prospects.index')" wire:navigate variant="ghost" />
            <div>
                <flux:heading size="xl">{{ $prospect->name }}</flux:heading>
                <flux:badge color="{{ $prospect->status->color() }}" size="sm" class="mt-1">{{ $prospect->status->label() }}</flux:badge>
            </div>
        </div>
        @if (auth()->user()->isAdmin())
            <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('Are you sure you want to delete this prospect?') }}">
                {{ __('Delete') }}
            </flux:button>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left: Prospect Details --}}
        <div class="lg:col-span-2 flex flex-col gap-6">

            {{-- Personal Information --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Personal Information') }}</flux:heading>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Email') }}</flux:text><flux:text>{{ $prospect->email }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Phone') }}</flux:text><flux:text>{{ $prospect->phone }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Date of Birth') }}</flux:text><flux:text>{{ $prospect->date_of_birth?->format('M j, Y') ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Gender') }}</flux:text><flux:text>{{ $prospect->gender ?? '—' }}</flux:text></div>
                    <div class="sm:col-span-2"><flux:text class="text-xs text-zinc-500">{{ __('Address') }}</flux:text><flux:text>{{ $prospect->address ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Emergency Contact') }}</flux:text><flux:text>{{ $prospect->emergency_contact_name ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Emergency Phone') }}</flux:text><flux:text>{{ $prospect->emergency_contact_phone ?? '—' }}</flux:text></div>
                </dl>
            </div>

            {{-- Contact Preferences --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Contact Preferences') }}</flux:heading>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Preferred Method') }}</flux:text><flux:text>{{ $prospect->preferred_contact_method->label() }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Best Time') }}</flux:text><flux:text>{{ $prospect->best_time_to_contact->label() }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Heard About Us') }}</flux:text><flux:text>{{ $prospect->heard_about_us ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Financing Interest') }}</flux:text><flux:text>{{ $prospect->financing_interest ? __('Yes') : __('No') }}</flux:text></div>
                </dl>
            </div>

            {{-- Background --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Background') }}</flux:heading>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Cohort') }}</flux:text><flux:text>{{ $prospect->cohort ? $prospect->cohort->name.' — '.$prospect->cohort->start_date->format('M j, Y') : '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Education Level') }}</flux:text><flux:text>{{ $prospect->highest_education_level?->label() ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Employment Status') }}</flux:text><flux:text>{{ $prospect->employment_status?->label() ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Entry Point') }}</flux:text><flux:text>{{ $prospect->entry_point->label() }}</flux:text></div>
                    @if ($prospect->goals)
                        <div class="sm:col-span-2">
                            <flux:text class="text-xs text-zinc-500">{{ __('Goals') }}</flux:text>
                            <flux:text>{{ $prospect->goals }}</flux:text>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Log Activity --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Log Activity') }}</flux:heading>
                <form wire:submit="logActivity" class="flex flex-col gap-4">
                    <flux:field>
                        <flux:label>{{ __('Type') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                        <flux:select wire:model="activityAction">
                            <flux:select.option value="">{{ __('Select activity type...') }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::Call->value }}">{{ App\Enums\ActivityAction::Call->label() }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::Email->value }}">{{ App\Enums\ActivityAction::Email->label() }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::Text->value }}">{{ App\Enums\ActivityAction::Text->label() }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::WalkIn->value }}">{{ App\Enums\ActivityAction::WalkIn->label() }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::Note->value }}">{{ App\Enums\ActivityAction::Note->label() }}</flux:select.option>
                        </flux:select>
                        <flux:error name="activityAction" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Notes') }}</flux:label>
                        <flux:textarea wire:model="activityNotes" rows="3" placeholder="{{ __('What happened during this interaction?') }}" />
                        <flux:error name="activityNotes" />
                    </flux:field>
                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">{{ __('Log Activity') }}</flux:button>
                    </div>
                </form>
            </div>

            {{-- Activity Timeline --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Activity Timeline') }}</flux:heading>
                <div class="flex flex-col gap-3">
                    @forelse ($prospect->activities as $activity)
                        <div wire:key="{{ $activity->id }}" class="flex gap-3 text-sm">
                            <div class="flex flex-col items-center">
                                <div class="flex size-7 items-center justify-center rounded-full {{ $activity->type === App\Enums\ActivityType::Automatic ? 'bg-zinc-100 dark:bg-zinc-700' : 'bg-blue-100 dark:bg-blue-900' }}">
                                    <flux:icon name="{{ $activity->type === App\Enums\ActivityType::Automatic ? 'arrow-path' : 'chat-bubble-left' }}" class="size-3.5 {{ $activity->type === App\Enums\ActivityType::Automatic ? 'text-zinc-500' : 'text-blue-600' }}" />
                                </div>
                                <div class="mt-1 flex-1 border-l border-zinc-200 dark:border-zinc-700"></div>
                            </div>
                            <div class="pb-4 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $activity->action->label() }}</span>
                                    @if ($activity->performedBy)
                                        <span class="text-zinc-500">{{ __('by') }} {{ $activity->performedBy->name }}</span>
                                    @endif
                                    <span class="text-zinc-400 text-xs ml-auto">{{ $activity->created_at->format('M j, Y g:i a') }}</span>
                                </div>
                                @if ($activity->from_value && $activity->to_value)
                                    <flux:text class="text-zinc-500 text-sm mt-1">
                                        {{ $activity->from_value }} → {{ $activity->to_value }}
                                    </flux:text>
                                @endif
                                @if ($activity->notes)
                                    <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $activity->notes }}</flux:text>
                                @endif
                            </div>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500">{{ __('No activity recorded yet.') }}</flux:text>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex flex-col gap-6">
            {{-- Status --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Status') }}</flux:heading>
                <form wire:submit="updateStatus" class="flex flex-col gap-3">
                    <flux:select wire:model="newStatus">
                        @foreach (ProspectStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:button type="submit" variant="primary" class="w-full">{{ __('Update Status') }}</flux:button>
                </form>
            </div>

            {{-- Assignment (admin only) --}}
            @if (auth()->user()->isAdmin())
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:heading class="mb-4">{{ __('Assignment') }}</flux:heading>
                    <form wire:submit="updateAssignment" class="flex flex-col gap-3">
                        <flux:select wire:model="newAssignedTo">
                            <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                            @foreach ($this->staffMembers as $staff)
                                <flux:select.option value="{{ $staff->id }}">{{ $staff->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button type="submit" variant="primary" class="w-full">{{ __('Reassign') }}</flux:button>
                    </form>
                </div>
            @endif

            {{-- Meta --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Details') }}</flux:heading>
                <dl class="flex flex-col gap-2">
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Submitted') }}</flux:text><flux:text class="text-sm">{{ $prospect->created_at->format('M j, Y g:i a') }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Last Updated') }}</flux:text><flux:text class="text-sm">{{ $prospect->updated_at->format('M j, Y g:i a') }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Assigned To') }}</flux:text><flux:text class="text-sm">{{ $prospect->assignedTo?->name ?? '—' }}</flux:text></div>
                </dl>
            </div>
        </div>
    </div>
</div>
