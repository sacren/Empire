<?php

use App\Models\Program;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Program')] class extends Component {
    public Program $program;
    public string $name = '';
    public string $description = '';
    public string $default_tuition = '';
    public bool $is_active = true;

    public function mount(Program $program): void
    {
        $this->authorize('update', $program);
        $this->program = $program;
        $this->name = $program->name;
        $this->description = $program->description ?? '';
        $this->default_tuition = $program->default_tuition !== null ? (string) $program->default_tuition : '';
        $this->is_active = $program->is_active;
    }

    public function save(): void
    {
        $this->authorize('update', $this->program);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_tuition' => ['nullable', 'numeric', 'min:0.01'],
            'is_active' => ['boolean'],
        ]);

        $validated['default_tuition'] = $validated['default_tuition'] ?: null;

        $this->program->update($validated);

        $this->redirectRoute('cohorts.index', navigate: true);
    }

    public function deleteProgram(): void
    {
        $this->authorize('delete', $this->program);

        $this->program->delete();

        $this->redirectRoute('cohorts.index', navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button icon="arrow-left" :href="route('cohorts.index')" wire:navigate variant="ghost" />
        <flux:heading size="xl">{{ __('Edit Program') }}</flux:heading>
    </div>

    <div class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:field>
                <flux:label>{{ __('Program Name') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="name" type="text" />
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
                @can('delete', $program)
                    <flux:button variant="danger" wire:click="deleteProgram" wire:confirm="{{ __('Delete this program? This cannot be undone.') }}" :loading="false">
                        {{ __('Delete') }}
                    </flux:button>
                @endcan
                <flux:spacer />
                <flux:button :href="route('cohorts.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Update Program') }}</flux:button>
            </div>
        </form>
    </div>
</div>
