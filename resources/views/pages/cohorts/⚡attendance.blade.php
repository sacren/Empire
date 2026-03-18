<?php

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Cohort;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Record Attendance')] class extends Component {
    public Cohort $cohort;
    public string $sessionDate = '';

    /** @var array<int, array{status: string, notes: string}> */
    public array $records = [];

    public function mount(Cohort $cohort): void
    {
        $this->authorize('attendance', $cohort);
        $this->cohort = $cohort;
        $this->sessionDate = now()->format('Y-m-d');
        $this->initializeRecords();
        $this->loadExistingRecords();
    }

    #[Computed]
    public function enrollments(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->cohort->enrollments()->with('prospect')->get();
    }

    public function updatedSessionDate(): void
    {
        $this->initializeRecords();
        $this->loadExistingRecords();
    }

    public function saveAttendance(): void
    {
        $this->authorize('attendance', $this->cohort);

        $this->validate([
            'sessionDate' => ['required', 'date'],
        ]);

        foreach ($this->records as $enrollmentId => $record) {
            AttendanceRecord::updateOrCreate(
                [
                    'enrollment_id' => $enrollmentId,
                    'session_date' => $this->sessionDate,
                ],
                [
                    'status' => $record['status'],
                    'notes' => $record['notes'] ?: null,
                ],
            );
        }

        session()->flash('success', __('Attendance saved successfully.'));
    }

    private function initializeRecords(): void
    {
        $this->records = [];

        foreach ($this->enrollments as $enrollment) {
            $this->records[$enrollment->id] = [
                'status' => AttendanceStatus::Present->value,
                'notes' => '',
            ];
        }
    }

    private function loadExistingRecords(): void
    {
        if (! $this->sessionDate) {
            return;
        }

        $enrollmentIds = array_keys($this->records);

        $existing = AttendanceRecord::where('session_date', $this->sessionDate)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->get()
            ->keyBy('enrollment_id');

        foreach ($existing as $enrollmentId => $record) {
            $this->records[$enrollmentId] = [
                'status' => $record->status->value,
                'notes' => $record->notes ?? '',
            ];
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Record Attendance') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">{{ $cohort->name }}</flux:text>
        </div>
        <flux:button :href="route('cohorts.index')" wire:navigate>{{ __('Back to Cohorts') }}</flux:button>
    </div>

    @if (session('success'))
        <div class="mb-4">
            <flux:callout variant="success">{{ session('success') }}</flux:callout>
        </div>
    @endif

    <div class="mb-6 max-w-xs">
        <flux:input type="date" wire:model.live="sessionDate" label="{{ __('Session Date') }}" />
    </div>

    @if ($this->enrollments->isEmpty())
        <flux:text class="text-zinc-500">{{ __('No students enrolled in this cohort.') }}</flux:text>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Student') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->enrollments as $enrollment)
                        <tr wire:key="{{ $enrollment->id }}">
                            <td class="px-4 py-3">
                                <flux:text class="font-medium">{{ $enrollment->prospect->first_name }} {{ $enrollment->prospect->last_name }}</flux:text>
                            </td>
                            <td class="px-4 py-3">
                                <flux:select wire:model="records.{{ $enrollment->id }}.status" size="sm">
                                    @foreach (AttendanceStatus::cases() as $status)
                                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </td>
                            <td class="px-4 py-3">
                                <flux:input wire:model="records.{{ $enrollment->id }}.notes" size="sm" placeholder="{{ __('Optional') }}" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <flux:button variant="primary" wire:click="saveAttendance">{{ __('Save Attendance') }}</flux:button>
        </div>
    @endif
</div>
