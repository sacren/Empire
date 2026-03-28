<?php

use App\Models\Program;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add Program')] class extends Component {
    public string $name = '';
    public string $description = '';
    public string $default_tuition = '';
    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('create', Program::class);
    }

    public function save(): void
    {
        $this->authorize('create', Program::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_tuition' => ['nullable', 'numeric', 'min:0.01'],
            'is_active' => ['boolean'],
        ]);

        $validated['default_tuition'] = $validated['default_tuition'] ?: null;

        Program::create($validated);

        $this->redirectRoute('cohorts.index', navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button icon="arrow-left" :href="route('cohorts.index')" wire:navigate variant="ghost" />
        <flux:heading size="xl">{{ __('Add Program') }}</flux:heading>
    </div>

    <div class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:field>
                <flux:label>{{ __('Program Name') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="name" type="text" placeholder="{{ __('e.g. SkillPath') }}" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Description') }}</flux:label>
                <flux:textarea wire:model="description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Default Tuition') }}</flux:label>
                <flux:input wire:model="default_tuition" type="number" step="0.01" min="0.01" placeholder="{{ __('Leave blank if not set') }}" />
                <flux:error name="default_tuition" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="is_active" label="{{ __('Active') }}" />
            </flux:field>

            <div class="flex gap-3 justify-end">
                <flux:button :href="route('cohorts.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Program') }}</flux:button>
            </div>
        </form>
    </div>
</div>
