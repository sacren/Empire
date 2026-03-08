<?php

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Staff')] class extends Component {
    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    #[Computed]
    public function staffMembers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()->where('role', UserRole::Staff->value)->orderBy('name')->get();
    }

    public function toggleStaff(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);
        $user->update(['is_active' => ! $user->is_active]);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Staff') }}</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('staff.create')" wire:navigate>
            {{ __('Add Staff Member') }}
        </flux:button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Email') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Assigned Prospects') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->staffMembers as $staff)
                    <tr wire:key="{{ $staff->id }}">
                        <td class="px-4 py-3"><flux:text class="font-medium">{{ $staff->name }}</flux:text></td>
                        <td class="px-4 py-3"><flux:text>{{ $staff->email }}</flux:text></td>
                        <td class="px-4 py-3"><flux:text>{{ $staff->assignedProspects()->count() }}</flux:text></td>
                        <td class="px-4 py-3">
                            <flux:badge color="{{ $staff->is_active ? 'green' : 'zinc' }}" size="sm">
                                {{ $staff->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <flux:button size="sm" wire:click="toggleStaff({{ $staff->id }})">
                                {{ $staff->is_active ? __('Deactivate') : __('Activate') }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center">
                            <flux:text class="text-zinc-500">{{ __('No staff members yet.') }}</flux:text>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
