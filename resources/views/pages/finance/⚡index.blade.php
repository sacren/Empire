<?php

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Finance')] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    /**
     * @return array{enrolled: int, tuition_set: int, total_tuition: float|int, total_collected: float|int, total_outstanding: float|int}
     */
    #[Computed]
    public function summary(): array
    {
        $enrollments = Enrollment::query()->with('payments')->get();

        $totalTuition = $enrollments->sum('amount_owed');
        $totalCollected = $enrollments->flatMap->payments->sum('amount');

        return [
            'enrolled' => $enrollments->count(),
            'tuition_set' => $enrollments->whereNotNull('amount_owed')->count(),
            'total_tuition' => $totalTuition,
            'total_collected' => $totalCollected,
            'total_outstanding' => max(0, $totalTuition - $totalCollected),
        ];
    }

    #[Computed]
    public function enrollments(): \Illuminate\Database\Eloquent\Collection
    {
        return Enrollment::query()
            ->with(['prospect', 'cohort', 'payments'])
            ->orderByDesc('enrolled_at')
            ->get();
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Finance') }}</flux:heading>
        <flux:subheading>{{ __('Overview of enrollments and tuition payments.') }}</flux:subheading>
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
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Cohort') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Tuition') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Paid') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Balance') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->enrollments as $enrollment)
                    <tr wire:key="{{ $enrollment->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3">
                            <flux:text class="font-medium">
                                <a href="{{ route('prospects.show', $enrollment->prospect) }}" wire:navigate class="hover:underline">
                                    {{ $enrollment->prospect->name }}
                                </a>
                            </flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">{{ $enrollment->cohort?->name ?? '—' }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">{{ $enrollment->amount_owed !== null ? '$'.number_format($enrollment->amount_owed, 2) : '—' }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">${{ number_format($enrollment->totalPaid(), 2) }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:text class="text-sm">{{ $enrollment->balance() !== null ? '$'.number_format($enrollment->balance(), 2) : '—' }}</flux:text>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge color="{{ $enrollment->status->color() }}" size="sm">{{ $enrollment->status->label() }}</flux:badge>
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
