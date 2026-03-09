<?php

use App\Enums\ProspectStatus;
use App\Enums\UserRole;
use App\Models\Cohort;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function prospectPipeline(): \Illuminate\Support\Collection
    {
        $counts = Prospect::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(ProspectStatus::cases())->map(fn ($status) => [
            'status' => $status,
            'count' => (int) ($counts[$status->value] ?? 0),
        ]);
    }

    #[Computed]
    public function prospectsToday(): int
    {
        return Prospect::query()->whereDate('created_at', today())->count();
    }

    #[Computed]
    public function prospectsThisWeek(): int
    {
        return Prospect::query()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
    }

    #[Computed]
    public function unassignedCount(): int
    {
        return Prospect::query()->whereNull('assigned_to')->count();
    }

    #[Computed]
    public function staffWorkload(): \Illuminate\Database\Eloquent\Collection
    {
        if (! auth()->user()->isAdmin()) {
            return collect();
        }

        return User::query()
            ->where('role', UserRole::Staff->value)
            ->where('is_active', true)
            ->withCount('assignedProspects')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function cohortCapacity(): \Illuminate\Database\Eloquent\Collection
    {
        if (! auth()->user()->isAdmin()) {
            return collect();
        }

        return Cohort::query()
            ->where('is_active', true)
            ->withCount('prospects')
            ->orderBy('start_date')
            ->get();
    }

    #[Computed]
    public function recentActivity(): \Illuminate\Database\Eloquent\Collection
    {
        return ProspectActivity::query()
            ->latest()
            ->limit(10)
            ->with(['prospect', 'performedBy'])
            ->get();
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <div class="flex gap-2">
            <flux:button variant="primary" icon="plus" :href="route('prospects.create')" wire:navigate>
                {{ __('Add Prospect') }}
            </flux:button>
            @if (auth()->user()->isAdmin())
                <flux:button icon="users" :href="route('prospects.index')" wire:navigate>
                    {{ __('View Unassigned') }}
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">{{ __('New Today') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->prospectsToday }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">{{ __('New This Week') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->prospectsThisWeek }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">{{ __('Unassigned') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->unassignedCount }}</flux:heading>
        </div>
    </div>

    {{-- Prospect pipeline --}}
    <div class="mb-6">
        <flux:heading class="mb-3">{{ __('Prospect Pipeline') }}</flux:heading>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
            @foreach ($this->prospectPipeline as $item)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:badge color="{{ $item['status']->color() }}" size="sm">
                        {{ $item['status']->label() }}
                    </flux:badge>
                    <flux:heading size="xl" class="mt-2">{{ $item['count'] }}</flux:heading>
                </div>
            @endforeach
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <div class="mb-6 grid gap-6 md:grid-cols-2">
            {{-- Staff workload --}}
            <div>
                <flux:heading class="mb-3">{{ __('Staff Workload') }}</flux:heading>
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Staff Member') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Assigned') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                            @forelse ($this->staffWorkload as $staff)
                                <tr wire:key="{{ $staff->id }}">
                                    <td class="px-4 py-3">
                                        <flux:text>{{ $staff->name }}</flux:text>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:badge color="{{ $staff->assigned_prospects_count > 0 ? 'blue' : 'zinc' }}" size="sm">
                                            {{ $staff->assigned_prospects_count }}
                                        </flux:badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-6 text-center">
                                        <flux:text class="text-zinc-500">{{ __('No active staff members.') }}</flux:text>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Cohort capacity --}}
            <div>
                <flux:heading class="mb-3">{{ __('Cohort Capacity') }}</flux:heading>
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Cohort') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Prospects / Capacity') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                            @forelse ($this->cohortCapacity as $cohort)
                                <tr wire:key="{{ $cohort->id }}">
                                    <td class="px-4 py-3">
                                        <flux:text>{{ $cohort->name }}</flux:text>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:text>{{ $cohort->prospects_count }} / {{ $cohort->capacity ?? __('Unlimited') }}</flux:text>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-6 text-center">
                                        <flux:text class="text-zinc-500">{{ __('No active cohorts.') }}</flux:text>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent activity --}}
        <div>
            <flux:heading class="mb-3">{{ __('Recent Activity') }}</flux:heading>
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Prospect') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Action') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Performed By') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('When') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @forelse ($this->recentActivity as $activity)
                            <tr wire:key="{{ $activity->id }}">
                                <td class="px-4 py-3">
                                    <flux:text>{{ $activity->prospect->name }}</flux:text>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:text>{{ $activity->action->label() }}</flux:text>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:text>{{ $activity->performedBy?->name ?? __('System') }}</flux:text>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:text>{{ $activity->created_at->diffForHumans() }}</flux:text>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center">
                                    <flux:text class="text-zinc-500">{{ __('No recent activity.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- Staff view: their assigned prospects --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">{{ __('Your Assigned Prospects') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ auth()->user()->assignedProspects()->count() }}</flux:heading>
            <div class="mt-4">
                <flux:button :href="route('prospects.index')" wire:navigate>
                    {{ __('View My Prospects') }}
                </flux:button>
            </div>
        </div>
    @endif
</div>
