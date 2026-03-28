<?php

use App\Enums\ProspectStatus;
use App\Models\Program;
use App\Models\Prospect;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Finance')] class extends Component {
    #[Url]
    public string $programFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    #[Computed]
    public function programs(): \Illuminate\Database\Eloquent\Collection
    {
        return Program::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return array{enrolled: int, tuition_set: int, total_tuition: float|int, total_collected: float|int, total_outstanding: float|int}
     */
    #[Computed]
    public function summary(): array
    {
        $prospects = $this->enrolledProspects;

        $totalTuition = $prospects->sum(fn ($p) => (float) ($p->enrollment?->amount_owed ?? 0));
        $totalCollected = $prospects->sum(fn ($p) => $p->enrollment?->totalPaid() ?? 0);

        return [
            'enrolled' => $prospects->count(),
            'tuition_set' => $prospects->filter(fn ($p) => $p->enrollment?->amount_owed !== null)->count(),
            'total_tuition' => $totalTuition,
            'total_collected' => $totalCollected,
            'total_outstanding' => max(0, $totalTuition - $totalCollected),
        ];
    }

    #[Computed]
    public function enrolledProspects(): \Illuminate\Database\Eloquent\Collection
    {
        return Prospect::query()
            ->where('status', ProspectStatus::Enrolled)
            ->with(['enrollment.payments', 'cohort.program'])
            ->when($this->programFilter, fn ($q) => $q->whereHas('cohort', fn ($cq) => $cq->where('program_id', $this->programFilter)))
            ->orderByDesc('updated_at')
            ->get();
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Finance') }}</flux:heading>
            <flux:subheading>{{ __('Overview of enrollments and tuition payments.') }}</flux:subheading>
        </div>
        <flux:select wire:model.live="programFilter" class="w-48">
            <flux:select.option value="">{{ __('All Programs') }}</flux:select.option>
            @foreach ($this->programs as $program)
                <flux:select.option value="{{ $program->id }}">{{ $program->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
            <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">{{ __('Total Enrolled') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->summary['enrolled'] }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
            <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">{{ __('Tuition Set') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->summary['tuition_set'] }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
            <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">{{ __('Total Collected') }}</flux:text>
            <flux:heading size="xl" class="mt-1">${{ number_format($this->summary['total_collected'], 2) }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
            <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">{{ __('Outstanding') }}</flux:text>
            <flux:heading size="xl" class="mt-1">${{ number_format($this->summary['total_outstanding'], 2) }}</flux:heading>
        </div>
    </div>

    {{-- Enrollments Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Prospect') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Program / Cohort') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Tuition') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Paid') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Balance') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->enrolledProspects as $prospect)
                    <tr wire:key="{{ $prospect->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3">
                            <flux:text class="font-medium">
                                <a href="{{ route('prospects.show', $prospect) }}" wire:navigate class="hover:underline">
                                    {{ $prospect->name }}
                                </a>
                            </flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">{{ $prospect->cohort ? $prospect->cohort->program->name.': '.$prospect->cohort->name : '—' }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">{{ $prospect->enrollment?->amount_owed !== null ? '$'.number_format($prospect->enrollment->amount_owed, 2) : '—' }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">${{ number_format($prospect->enrollment?->totalPaid() ?? 0, 2) }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">{{ $prospect->enrollment?->balance() !== null ? '$'.number_format($prospect->enrollment->balance(), 2) : '—' }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            @if ($prospect->enrollment)
                                <flux:badge color="{{ $prospect->enrollment->status->color() }}" size="sm">{{ $prospect->enrollment->status->label() }}</flux:badge>
                            @else
                                <flux:text class="text-sm">—</flux:text>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center">
                            <flux:text class="text-zinc-500">{{ __('No enrollments found.') }}</flux:text>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
