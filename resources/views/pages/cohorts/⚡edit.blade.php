<?php

use App\Models\Cohort;
use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Cohort')] class extends Component {
    public Cohort $cohort;
    public string $program_id = '';
    public string $name = '';
    public string $start_date = '';
    public string $capacity = '';

    public function mount(Cohort $cohort): void
    {
        $this->authorize('update', $cohort);
        $this->cohort = $cohort;
        $this->program_id = (string) $cohort->program_id;
        $this->name = $cohort->name;
        $this->start_date = $cohort->start_date->format('Y-m-d');
        $this->capacity = (string) ($cohort->capacity ?? '');
    }

    #[Computed]
    public function programs(): \Illuminate\Database\Eloquent\Collection
    {
        return Program::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function save(): void
    {
        $this->authorize('update', $this->cohort);

        $validated = $this->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['capacity'] = $validated['capacity'] ?: null;

        $this->cohort->update($validated);

        $this->redirectRoute('cohorts.index', navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button icon="arrow-left" :href="route('cohorts.index')" wire:navigate variant="ghost" />
        <flux:heading size="xl">{{ __('Edit Cohort') }}</flux:heading>
    </div>

    <div class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:field>
                <flux:label>{{ __('Program') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:select wire:model="program_id">
                    @foreach ($this->programs as $program)
                        <flux:select.option value="{{ $program->id }}">{{ $program->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="program_id" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Cohort Name') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Start Date') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="start_date" type="date" />
                <flux:error name="start_date" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Capacity') }}</flux:label>
                <flux:input wire:model="capacity" type="number" min="1" placeholder="{{ __('Leave blank for unlimited') }}" />
                <flux:error name="capacity" />
            </flux:field>

            <div class="flex gap-3 justify-end">
                <flux:button :href="route('cohorts.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Update Cohort') }}</flux:button>
            </div>
        </form>
    </div>
</div>
