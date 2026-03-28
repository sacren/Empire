<?php

use App\Models\Cohort;
use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Programs & Cohorts')] class extends Component {
    public function mount(): void
    {
        $this->authorize('viewAny', Cohort::class);
    }

    #[Computed]
    public function programs(): \Illuminate\Database\Eloquent\Collection
    {
        return Program::query()->with(['cohorts' => fn ($q) => $q->orderBy('start_date')])->orderBy('name')->get();
    }

    public function toggleCohort(int $cohortId): void
    {
        $cohort = Cohort::findOrFail($cohortId);
        $this->authorize('update', $cohort);
        $cohort->update(['is_active' => ! $cohort->is_active]);
    }

    public function deleteCohort(int $cohortId): void
    {
        $cohort = Cohort::findOrFail($cohortId);
        $this->authorize('delete', $cohort);
        $cohort->delete();
    }

    public function deleteProgram(int $programId): void
    {
        $program = Program::findOrFail($programId);
        $this->authorize('delete', $program);
        $program->delete();
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Programs & Cohorts') }}</flux:heading>
        <div class="flex items-center gap-2">
            <flux:button icon="plus" :href="route('programs.create')" wire:navigate>
                {{ __('Add Program') }}
            </flux:button>
            <flux:button variant="primary" icon="plus" :href="route('cohorts.create')" wire:navigate>
                {{ __('Add Cohort') }}
            </flux:button>
        </div>
    </div>

    @forelse ($this->programs as $program)
        <div class="mb-6" wire:key="program-{{ $program->id }}">
            <div class="flex items-center gap-3 mb-3">
                <flux:heading>{{ $program->name }}</flux:heading>
                @unless ($program->is_active)
                    <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                @endunless
                @if ($program->default_tuition)
                    <flux:text class="text-sm text-zinc-500">{{ __('Default tuition: $:amount', ['amount' => number_format($program->default_tuition, 2)]) }}</flux:text>
                @endif
                <flux:button size="sm" :href="route('programs.edit', $program)" wire:navigate>{{ __('Edit') }}</flux:button>
                @can('delete', $program)
                    <flux:button size="sm" variant="danger" wire:click="deleteProgram({{ $program->id }})" wire:confirm="{{ __('Delete this program? This cannot be undone.') }}" :loading="false">{{ __('Delete') }}</flux:button>
                @endcan
            </div>
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Start Date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Capacity') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Prospects') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($program->cohorts as $cohort)
                            <tr wire:key="{{ $cohort->id }}">
                                <td class="px-4 py-3"><flux:text class="font-medium">{{ $cohort->name }}</flux:text></td>
                                <td class="px-4 py-3"><flux:text>{{ $cohort->start_date->format('M j, Y') }}</flux:text></td>
                                <td class="px-4 py-3"><flux:text>{{ $cohort->capacity ?? '—' }}</flux:text></td>
                                <td class="px-4 py-3"><flux:text>{{ $cohort->prospects_count ?? $cohort->prospects()->count() }}</flux:text></td>
                                <td class="px-4 py-3">
                                    <flux:badge color="{{ $cohort->is_active ? 'green' : 'zinc' }}" size="sm">
                                        {{ $cohort->is_active ? __('Active') : __('Inactive') }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button size="sm" :href="route('cohorts.attendance', $cohort)" wire:navigate>{{ __('Attendance') }}</flux:button>
                                        <flux:button size="sm" :href="route('cohorts.edit', $cohort)" wire:navigate>{{ __('Edit') }}</flux:button>
                                        <flux:button size="sm" wire:click="toggleCohort({{ $cohort->id }})">
                                            {{ $cohort->is_active ? __('Deactivate') : __('Activate') }}
                                        </flux:button>
                                        <flux:button size="sm" variant="danger" wire:click="deleteCohort({{ $cohort->id }})" wire:confirm="{{ __('Delete this cohort?') }}">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center">
                                    <flux:text class="text-zinc-500">{{ __('No cohorts yet.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <flux:text class="text-zinc-500">{{ __('No programs found.') }}</flux:text>
    @endforelse
</div>
