<?php

use Livewire\Component;

new class extends Component {
    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        $prospectId = $notification->data['prospect_id'] ?? null;

        if ($prospectId) {
            $this->redirect(route('prospects.show', $prospectId), navigate: true);
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'notifications' => $user->notifications()->latest()->take(10)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ];
    }
}; ?>

<div>
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" square size="sm" aria-label="Notifications" class="relative">
            <flux:icon.bell variant="mini" />
            @if ($unreadCount > 0)
                <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            <div class="flex items-center justify-between px-3 py-2">
                <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>
                @if ($unreadCount > 0)
                    <flux:button variant="ghost" size="xs" wire:click="markAllAsRead">
                        {{ __('Mark all as read') }}
                    </flux:button>
                @endif
            </div>

            <flux:menu.separator />

            @forelse ($notifications as $notification)
                <flux:menu.item
                    wire:key="notification-{{ $notification->id }}"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="{{ is_null($notification->read_at) ? 'bg-zinc-50 dark:bg-zinc-700/50' : '' }}"
                >
                    <div class="flex w-full flex-col gap-0.5">
                        <div class="flex items-start gap-2">
                            @if (is_null($notification->read_at))
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                            @endif
                            <span class="text-sm">{{ $notification->data['message'] ?? 'New notification' }}</span>
                        </div>
                        <span class="text-xs text-zinc-500 {{ is_null($notification->read_at) ? 'pl-4' : '' }}">
                            {{ $notification->created_at->diffForHumans() }}
                        </span>
                    </div>
                </flux:menu.item>
            @empty
                <div class="px-3 py-6 text-center">
                    <flux:text size="sm">{{ __('No notifications') }}</flux:text>
                </div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>
