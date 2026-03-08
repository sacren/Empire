<?php

use App\Enums\ProspectStatus;
use App\Enums\UserRole;
use App\Models\Prospect;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Prospects')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    #[Computed]
    public function prospects(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $user = auth()->user();

        return Prospect::query()
            ->with(['cohort', 'assignedTo'])
            ->when($user->role === UserRole::Staff, fn ($q) => $q->where('assigned_to', $user->id))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(20);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Prospects') }}</flux:heading>
        @if (auth()->user()->isAdmin())
            <flux:button variant="primary" icon="plus" :href="route('prospects.create')" wire:navigate>
                {{ __('Add Prospect') }}
            </flux:button>
        @endif
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center mb-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by name, email or phone...') }}" />
        </div>
        <flux:select wire:model.live="statusFilter" class="sm:w-48">
            <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
            @foreach (ProspectStatus::cases() as $status)
                <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th wire:click="sortBy('name')" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider cursor-pointer select-none">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Contact') }}</th>
                    <th wire:click="sortBy('status')" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider cursor-pointer select-none">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Cohort') }}</th>
                    @if (auth()->user()->isAdmin())
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Assigned To') }}</th>
                    @endif
                    <th wire:click="sortBy('created_at')" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider cursor-pointer select-none">{{ __('Submitted') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->prospects as $prospect)
                    <tr wire:key="{{ $prospect->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3">
                            <flux:text class="font-medium">{{ $prospect->name }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">{{ $prospect->email }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ $prospect->phone }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge color="{{ $prospect->status->color() }}" size="sm">{{ $prospect->status->label() }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">{{ $prospect->cohort?->name ?? '—' }}</flux:text>
                        </td>
                        @if (auth()->user()->isAdmin())
                            <td class="px-4 py-3">
                                <flux:text class="text-sm">{{ $prospect->assignedTo?->name ?? '—' }}</flux:text>
                            </td>
                        @endif
                        <td class="px-4 py-3">
                            <flux:text class="text-sm text-zinc-500">{{ $prospect->created_at->format('M j, Y') }}</flux:text>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <flux:button size="sm" :href="route('prospects.show', $prospect)" wire:navigate>{{ __('View') }}</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 7 : 6 }}" class="px-4 py-8 text-center">
                            <flux:text class="text-zinc-500">{{ __('No prospects found.') }}</flux:text>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->prospects->links() }}
    </div>
</div>
