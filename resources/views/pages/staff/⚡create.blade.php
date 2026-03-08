<?php

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add Staff Member')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => UserRole::Staff,
            'is_active' => true,
        ]);

        $this->redirectRoute('staff.index', navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button icon="arrow-left" :href="route('staff.index')" wire:navigate variant="ghost" />
        <flux:heading size="xl">{{ __('Add Staff Member') }}</flux:heading>
    </div>

    <div class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:field>
                <flux:label>{{ __('Full Name') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Email Address') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="email" type="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Password') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="password" type="password" />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Confirm Password') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="password_confirmation" type="password" />
                <flux:error name="password_confirmation" />
            </flux:field>

            <div class="flex gap-3 justify-end">
                <flux:button :href="route('staff.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create Staff Member') }}</flux:button>
            </div>
        </form>
    </div>
</div>
