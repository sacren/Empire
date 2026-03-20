<?php

use App\Enums\ProspectStatus;
use App\Enums\UserRole;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Reports')] class extends Component {
    #[Url]
    public string $tab = 'enrollment-trends';

    #[Url]
    public string $period = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    /**
     * @return array{start: ?\Carbon\Carbon, end: \Carbon\Carbon}
     */
    protected function periodRange(): array
    {
        return match ($this->period) {
            'month' => ['start' => now()->startOfMonth(), 'end' => now()],
            'quarter' => ['start' => now()->startOfQuarter(), 'end' => now()],
            'year' => ['start' => now()->startOfYear(), 'end' => now()],
            default => ['start' => null, 'end' => now()],
        };
    }

    // ── Enrollment Trends ──────────────────────────────────────────────

    #[Computed]
    public function enrollmentTrends(): \Illuminate\Support\Collection
    {
        $query = Enrollment::query();

        $range = $this->periodRange();
        if ($range['start']) {
            $query->where('enrolled_at', '>=', $range['start']);
        }

        return $query
            ->selectRaw("to_char(enrolled_at, 'YYYY-MM') as month, count(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');
    }

    #[Computed]
    public function enrollmentSummary(): array
    {
        $range = $this->periodRange();

        $query = Enrollment::query();
        if ($range['start']) {
            $query->where('enrolled_at', '>=', $range['start']);
        }

        return [
            'total' => $query->count(),
            'this_month' => Enrollment::query()->where('enrolled_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    // ── Revenue ────────────────────────────────────────────────────────

    #[Computed]
    public function revenueByCohort(): \Illuminate\Support\Collection
    {
        $range = $this->periodRange();

        return Cohort::query()
            ->whereHas('enrollments')
            ->with(['enrollments' => function ($q) use ($range) {
                $q->with(['payments' => function ($pq) use ($range) {
                    if ($range['start']) {
                        $pq->where('paid_at', '>=', $range['start']);
                    }
                }]);
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($cohort) {
                $tuition = $cohort->enrollments->sum(fn ($e) => (float) ($e->amount_owed ?? 0));
                $collected = $cohort->enrollments->sum(fn ($e) => $e->payments->sum('amount'));

                return [
                    'name' => $cohort->name,
                    'tuition' => $tuition,
                    'collected' => $collected,
                    'outstanding' => max(0, $tuition - $collected),
                    'percent' => $tuition > 0 ? round(($collected / $tuition) * 100) : 0,
                ];
            });
    }

    #[Computed]
    public function revenueSummary(): array
    {
        $cohorts = $this->revenueByCohort;

        return [
            'total_tuition' => $cohorts->sum('tuition'),
            'total_collected' => $cohorts->sum('collected'),
            'total_outstanding' => $cohorts->sum('outstanding'),
        ];
    }

    // ── Cohort Utilization ─────────────────────────────────────────────

    #[Computed]
    public function cohortUtilization(): \Illuminate\Database\Eloquent\Collection
    {
        return Cohort::query()
            ->where('is_active', true)
            ->withCount('enrollments')
            ->orderBy('start_date')
            ->get();
    }

    // ── Staff Performance ──────────────────────────────────────────────

    #[Computed]
    public function staffPerformance(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('role', UserRole::Staff->value)
            ->where('is_active', true)
            ->with(['assignedProspects' => function ($q) {
                $q->select('id', 'assigned_to', 'status');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($staff) {
                $prospects = $staff->assignedProspects;
                $total = $prospects->count();
                $enrolled = $prospects->where('status', ProspectStatus::Enrolled)->count();
                $graduated = $prospects->where('status', ProspectStatus::Graduated)->count();

                return [
                    'name' => $staff->name,
                    'assigned' => $total,
                    'contacted' => $prospects->where('status', ProspectStatus::Contacted)->count(),
                    'qualified' => $prospects->where('status', ProspectStatus::Qualified)->count(),
                    'enrolled' => $enrolled,
                    'graduated' => $graduated,
                    'conversion_rate' => $total > 0 ? round((($enrolled + $graduated) / $total) * 100) : 0,
                ];
            });
    }

    // ── Graduation Rates ───────────────────────────────────────────────

    #[Computed]
    public function graduationRates(): \Illuminate\Support\Collection
    {
        return Cohort::query()
            ->whereHas('enrollments')
            ->withCount('enrollments')
            ->withCount(['enrollments as graduated_count' => function ($q) {
                $q->whereNotNull('graduated_at');
            }])
            ->orderBy('name')
            ->get()
            ->map(fn ($cohort) => [
                'name' => $cohort->name,
                'enrolled' => $cohort->enrollments_count,
                'graduated' => $cohort->graduated_count,
                'rate' => $cohort->enrollments_count > 0
                    ? round(($cohort->graduated_count / $cohort->enrollments_count) * 100)
                    : 0,
            ]);
    }

    #[Computed]
    public function graduationSummary(): array
    {
        $rates = $this->graduationRates;

        return [
            'total_enrolled' => $rates->sum('enrolled'),
            'total_graduated' => $rates->sum('graduated'),
            'overall_rate' => $rates->sum('enrolled') > 0
                ? round(($rates->sum('graduated') / $rates->sum('enrolled')) * 100)
                : 0,
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Reports') }}</flux:heading>
        <flux:subheading>{{ __('Data-driven insights for leadership decisions.') }}</flux:subheading>
    </div>

    {{-- Tab navigation --}}
    <div class="mb-6 flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
        @foreach ([
            'enrollment-trends' => __('Enrollment Trends'),
            'revenue' => __('Revenue'),
            'cohort-utilization' => __('Cohort Utilization'),
            'staff-performance' => __('Staff Performance'),
            'graduation-rates' => __('Graduation Rates'),
        ] as $key => $label)
            <button
                wire:click="$set('tab', '{{ $key }}')"
                class="-mb-px px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $tab === $key ? 'border-zinc-800 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Enrollment Trends --}}
    @if ($tab === 'enrollment-trends')
            {{-- Period filter --}}
            <div class="mb-6 flex items-center gap-3">
                <flux:select wire:model.live="period" class="w-48">
                    <flux:select.option value="all">{{ __('All Time') }}</flux:select.option>
                    <flux:select.option value="year">{{ __('This Year') }}</flux:select.option>
                    <flux:select.option value="quarter">{{ __('This Quarter') }}</flux:select.option>
                    <flux:select.option value="month">{{ __('This Month') }}</flux:select.option>
                </flux:select>
            </div>

            {{-- Summary cards --}}
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Total Enrolled') }}</flux:text>
                    <flux:heading size="xl" class="mt-1">{{ $this->enrollmentSummary['total'] }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Enrolled This Month') }}</flux:text>
                    <flux:heading size="xl" class="mt-1">{{ $this->enrollmentSummary['this_month'] }}</flux:heading>
                </div>
            </div>

            {{-- Trends table --}}
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Month') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Enrollments') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @forelse ($this->enrollmentTrends as $month => $count)
                            <tr wire:key="trend-{{ $month }}">
                                <td class="px-4 py-3">
                                    <flux:text>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</flux:text>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <flux:text class="font-medium">{{ $count }}</flux:text>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center">
                                    <flux:text class="text-zinc-500">{{ __('No enrollment data for this period.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    @endif

    {{-- Revenue --}}
    @if ($tab === 'revenue')
            {{-- Period filter --}}
            <div class="mb-6 flex items-center gap-3">
                <flux:select wire:model.live="period" class="w-48">
                    <flux:select.option value="all">{{ __('All Time') }}</flux:select.option>
                    <flux:select.option value="year">{{ __('This Year') }}</flux:select.option>
                    <flux:select.option value="quarter">{{ __('This Quarter') }}</flux:select.option>
                    <flux:select.option value="month">{{ __('This Month') }}</flux:select.option>
                </flux:select>
            </div>

            {{-- Summary cards --}}
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Total Tuition') }}</flux:text>
                    <flux:heading size="xl" class="mt-1">${{ number_format($this->revenueSummary['total_tuition'], 2) }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Total Collected') }}</flux:text>
                    <flux:heading size="xl" class="mt-1">${{ number_format($this->revenueSummary['total_collected'], 2) }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Outstanding') }}</flux:text>
                    <flux:heading size="xl" class="mt-1">${{ number_format($this->revenueSummary['total_outstanding'], 2) }}</flux:heading>
                </div>
            </div>

            {{-- Revenue table --}}
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Cohort') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Tuition') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Collected') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Outstanding') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('% Collected') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @forelse ($this->revenueByCohort as $row)
                            <tr wire:key="revenue-{{ $loop->index }}">
                                <td class="px-4 py-3"><flux:text class="font-medium">{{ $row['name'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>${{ number_format($row['tuition'], 2) }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>${{ number_format($row['collected'], 2) }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>${{ number_format($row['outstanding'], 2) }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $row['percent'] }}%</flux:text></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center">
                                    <flux:text class="text-zinc-500">{{ __('No revenue data available.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    @endif

    {{-- Cohort Utilization --}}
    @if ($tab === 'cohort-utilization')
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Cohort') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Start Date') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Capacity') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Enrolled') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Available') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Fill Rate') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @forelse ($this->cohortUtilization as $cohort)
                            <tr wire:key="util-{{ $cohort->id }}">
                                <td class="px-4 py-3"><flux:text class="font-medium">{{ $cohort->name }}</flux:text></td>
                                <td class="px-4 py-3"><flux:text>{{ $cohort->start_date->format('M j, Y') }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $cohort->capacity ?? __('Unlimited') }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $cohort->enrollments_count }}</flux:text></td>
                                <td class="px-4 py-3 text-right">
                                    <flux:text>{{ $cohort->capacity ? max(0, $cohort->capacity - $cohort->enrollments_count) : '—' }}</flux:text>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($cohort->capacity)
                                        <flux:text>{{ round(($cohort->enrollments_count / $cohort->capacity) * 100) }}%</flux:text>
                                    @else
                                        <flux:text>—</flux:text>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center">
                                    <flux:text class="text-zinc-500">{{ __('No active cohorts.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    @endif

    {{-- Staff Performance --}}
    @if ($tab === 'staff-performance')
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Staff Member') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Assigned') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Contacted') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Qualified') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Enrolled') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Graduated') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Conversion') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @forelse ($this->staffPerformance as $staff)
                            <tr wire:key="staff-{{ $loop->index }}">
                                <td class="px-4 py-3"><flux:text class="font-medium">{{ $staff['name'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $staff['assigned'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $staff['contacted'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $staff['qualified'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $staff['enrolled'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $staff['graduated'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right">
                                    <flux:badge color="{{ $staff['conversion_rate'] >= 50 ? 'green' : ($staff['conversion_rate'] >= 25 ? 'yellow' : 'zinc') }}" size="sm">
                                        {{ $staff['conversion_rate'] }}%
                                    </flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center">
                                    <flux:text class="text-zinc-500">{{ __('No active staff members.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    @endif

    {{-- Graduation Rates --}}
    @if ($tab === 'graduation-rates')
            {{-- Summary cards --}}
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Total Enrolled') }}</flux:text>
                    <flux:heading size="xl" class="mt-1">{{ $this->graduationSummary['total_enrolled'] }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Total Graduated') }}</flux:text>
                    <flux:heading size="xl" class="mt-1">{{ $this->graduationSummary['total_graduated'] }}</flux:heading>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Overall Rate') }}</flux:text>
                    <flux:heading size="xl" class="mt-1">{{ $this->graduationSummary['overall_rate'] }}%</flux:heading>
                </div>
            </div>

            {{-- Graduation table --}}
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Cohort') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Enrolled') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Graduated') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Rate') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @forelse ($this->graduationRates as $row)
                            <tr wire:key="grad-{{ $loop->index }}">
                                <td class="px-4 py-3"><flux:text class="font-medium">{{ $row['name'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $row['enrolled'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right"><flux:text>{{ $row['graduated'] }}</flux:text></td>
                                <td class="px-4 py-3 text-right">
                                    <flux:badge color="{{ $row['rate'] >= 75 ? 'green' : ($row['rate'] >= 50 ? 'yellow' : 'zinc') }}" size="sm">
                                        {{ $row['rate'] }}%
                                    </flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center">
                                    <flux:text class="text-zinc-500">{{ __('No enrollment data available.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    @endif
</div>
