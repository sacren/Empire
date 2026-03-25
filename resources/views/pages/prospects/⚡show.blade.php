<?php

use App\Enums\ActivityAction;
use App\Enums\ActivityType;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProspectStatus;
use App\Enums\UserRole;
use App\Mail\EnrollmentConfirmed;
use App\Mail\SendProspectEmail;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\MilestoneRecord;
use App\Models\Payment;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Prospect')] class extends Component {
    public Prospect $prospect;

    public string $activityAction = '';
    public string $activityNotes = '';
    public string $newStatus = '';
    public string $newAssignedTo = '';
    public string $enrollCohortId = '';
    public string $tuitionAmount = '';
    public string $paymentAmount = '';
    public string $paymentMethod = '';
    public string $paymentDate = '';
    public string $paymentNotes = '';
    public string $milestoneTitle = '';
    public string $milestoneNotes = '';
    public string $emailSubject = '';
    public string $emailBody = '';
    public string $graduationDate = '';
    public string $certificateNumber = '';
    public string $certificateIssuedAt = '';

    public function mount(Prospect $prospect): void
    {
        $this->authorize('view', $prospect);
        $this->prospect = $prospect->load(['cohort', 'assignedTo', 'activities.performedBy', 'enrollment.payments', 'enrollment.attendanceRecords', 'enrollment.milestoneRecords']);
        $this->newStatus = $prospect->status->value;
        $this->newAssignedTo = (string) ($prospect->assigned_to ?? '');
        $this->enrollCohortId = (string) ($prospect->cohort_id ?? '');
    }

    #[Computed]
    public function staffMembers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()->where('role', UserRole::Staff->value)->orderBy('name')->get();
    }

    #[Computed]
    public function activeCohorts(): \Illuminate\Database\Eloquent\Collection
    {
        return Cohort::query()->where('is_active', true)->orderBy('start_date')->get();
    }

    #[Computed]
    public function communicationLogs(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->prospect->communicationLogs()->with('sentBy')->get();
    }

    public function logActivity(): void
    {
        $this->validate([
            'activityAction' => ['required', 'string'],
            'activityNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        ProspectActivity::create([
            'prospect_id' => $this->prospect->id,
            'type' => ActivityType::Manual,
            'action' => $this->activityAction,
            'notes' => $this->activityNotes,
            'performed_by' => auth()->id(),
        ]);

        $this->activityAction = '';
        $this->activityNotes = '';
        $this->prospect->refresh()->load('activities.performedBy');
    }

    public function sendEmail(): void
    {
        $this->authorize('update', $this->prospect);
        $this->validate([
            'emailSubject' => ['required', 'string', 'max:255'],
            'emailBody' => ['required', 'string', 'max:10000'],
        ]);

        Mail::to($this->prospect->email)->send(new SendProspectEmail(
            $this->prospect,
            $this->emailSubject,
            $this->emailBody,
            auth()->id(),
        ));

        $this->emailSubject = '';
        $this->emailBody = '';
        $this->modal('send-email')->close();
        session()->flash('emailSent', true);
    }

    public function updateStatus(): void
    {
        $this->authorize('update', $this->prospect);
        $this->validate(['newStatus' => ['required', 'string']]);

        $this->prospect->update(['status' => $this->newStatus]);
        $this->prospect->refresh()->load('activities.performedBy');
    }

    public function updateAssignment(): void
    {
        $this->authorize('reassign', $this->prospect);
        $this->validate(['newAssignedTo' => ['nullable', 'exists:users,id']]);

        $this->prospect->update(['assigned_to' => $this->newAssignedTo ?: null]);
        $this->prospect->refresh()->load(['assignedTo', 'activities.performedBy']);
    }

    public function enroll(): void
    {
        $this->authorize('enroll', $this->prospect);
        $this->validate(['enrollCohortId' => ['required', 'exists:cohorts,id']]);

        $this->prospect->update(['status' => ProspectStatus::Enrolled]);

        $enrollment = Enrollment::create([
            'prospect_id' => $this->prospect->id,
            'cohort_id' => $this->enrollCohortId,
            'status' => 'pending',
            'enrolled_at' => now(),
        ]);

        Mail::to($this->prospect->email)->send(new EnrollmentConfirmed($enrollment));

        $this->modal('enroll-prospect')->close();
        $this->prospect->refresh()->load(['cohort', 'assignedTo', 'activities.performedBy', 'enrollment.payments', 'enrollment.attendanceRecords', 'enrollment.milestoneRecords']);
        $this->newStatus = $this->prospect->status->value;
    }

    public function setTuition(): void
    {
        $this->authorize('setTuition', $this->prospect->enrollment);
        $this->validate([
            'tuitionAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $this->prospect->enrollment->update(['amount_owed' => $this->tuitionAmount]);
        $this->prospect->enrollment->recalculateStatus();
        $this->modal('set-tuition')->close();
        $this->prospect->refresh()->load(['cohort', 'assignedTo', 'activities.performedBy', 'enrollment.payments', 'enrollment.attendanceRecords', 'enrollment.milestoneRecords']);
    }

    public function addPayment(): void
    {
        $this->authorize('createPayment', $this->prospect->enrollment);
        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'min:0.01'],
            'paymentMethod' => ['required', 'string'],
            'paymentDate' => ['required', 'date'],
        ]);

        Payment::create([
            'enrollment_id' => $this->prospect->enrollment->id,
            'amount' => $this->paymentAmount,
            'method' => $this->paymentMethod,
            'paid_at' => $this->paymentDate,
            'notes' => $this->paymentNotes ?: null,
        ]);

        $this->prospect->enrollment->refresh();

        $this->paymentAmount = '';
        $this->paymentMethod = '';
        $this->paymentDate = '';
        $this->paymentNotes = '';
        $this->modal('add-payment')->close();
        $this->prospect->refresh()->load(['cohort', 'assignedTo', 'activities.performedBy', 'enrollment.payments', 'enrollment.attendanceRecords', 'enrollment.milestoneRecords']);
    }

    public function addMilestone(): void
    {
        $this->authorize('manageMilestones', $this->prospect->enrollment);
        $this->validate([
            'milestoneTitle' => ['required', 'string', 'max:255'],
        ]);

        MilestoneRecord::create([
            'enrollment_id' => $this->prospect->enrollment->id,
            'title' => $this->milestoneTitle,
            'notes' => $this->milestoneNotes ?: null,
        ]);

        $this->milestoneTitle = '';
        $this->milestoneNotes = '';
        $this->modal('add-milestone')->close();
        $this->prospect->refresh()->load(['cohort', 'assignedTo', 'activities.performedBy', 'enrollment.payments', 'enrollment.attendanceRecords', 'enrollment.milestoneRecords']);
    }

    public function completeMilestone(int $id): void
    {
        $this->authorize('manageMilestones', $this->prospect->enrollment);

        $milestone = MilestoneRecord::findOrFail($id);
        $milestone->update(['completed_at' => now()]);

        $this->prospect->refresh()->load(['cohort', 'assignedTo', 'activities.performedBy', 'enrollment.payments', 'enrollment.attendanceRecords', 'enrollment.milestoneRecords']);
    }

    public function graduate(): void
    {
        $this->authorize('graduate', $this->prospect->enrollment);
        $this->validate([
            'graduationDate' => ['required', 'date'],
            'certificateNumber' => ['nullable', 'string', 'max:255'],
            'certificateIssuedAt' => ['nullable', 'date'],
        ]);

        $this->prospect->enrollment->update([
            'graduated_at' => $this->graduationDate,
            'certificate_number' => $this->certificateNumber ?: null,
            'certificate_issued_at' => $this->certificateIssuedAt ?: null,
        ]);

        $this->prospect->update(['status' => ProspectStatus::Graduated]);

        $this->modal('graduate-student')->close();
        $this->prospect->refresh()->load(['cohort', 'assignedTo', 'activities.performedBy', 'enrollment.payments', 'enrollment.attendanceRecords', 'enrollment.milestoneRecords']);
        $this->newStatus = $this->prospect->status->value;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->prospect);
        $this->prospect->delete();
        $this->redirectRoute('prospects.index', navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <flux:button icon="arrow-left" :href="route('prospects.index')" wire:navigate variant="ghost" />
            <div>
                <flux:heading size="xl">{{ $prospect->name }}</flux:heading>
                <flux:badge color="{{ $prospect->status->color() }}" size="sm" class="mt-1">{{ $prospect->status->label() }}</flux:badge>
            </div>
        </div>
        <div class="flex gap-2">
            @can('update', $prospect)
                <flux:modal.trigger name="send-email">
                    <flux:button icon="envelope" :loading="false">{{ __('Send Email') }}</flux:button>
                </flux:modal.trigger>
            @endcan
            @can('enroll', $prospect)
                <flux:modal.trigger name="enroll-prospect">
                    <flux:button variant="primary" icon="academic-cap">{{ __('Enroll') }}</flux:button>
                </flux:modal.trigger>
            @endcan
            @if (auth()->user()->isAdmin())
                <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('Are you sure you want to delete this prospect?') }}">
                    {{ __('Delete') }}
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Flash message --}}
    @if (session('emailSent'))
        <flux:badge color="green" size="lg" class="w-full justify-center">{{ __('Email queued for delivery.') }}</flux:badge>
    @endif

    {{-- Send Email Modal --}}
    <flux:modal name="send-email" class="max-w-lg">
        <form wire:submit="sendEmail" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Send Email') }}</flux:heading>
                <flux:subheading>{{ __('Compose and send an email to :name.', ['name' => $prospect->name]) }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Subject') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="emailSubject" placeholder="{{ __('Email subject...') }}" />
                <flux:error name="emailSubject" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Body') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:textarea wire:model="emailBody" rows="6" placeholder="{{ __('Write your message...') }}" />
                <flux:error name="emailBody" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Send') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Enroll Modal --}}
    <flux:modal name="enroll-prospect" class="max-w-lg">
        <form wire:submit="enroll" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Enroll Prospect') }}</flux:heading>
                <flux:subheading>{{ __('Confirm cohort placement and create an enrollment record.') }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Cohort') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:select wire:model.live="enrollCohortId">
                    <flux:select.option value="">{{ __('Select a cohort...') }}</flux:select.option>
                    @foreach ($this->activeCohorts as $cohort)
                        <flux:select.option value="{{ $cohort->id }}">{{ $cohort->name }} — {{ $cohort->start_date->format('M j, Y') }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="enrollCohortId" />
            </flux:field>

            @if ($enrollCohortId)
                @php $selectedCohort = $this->activeCohorts->firstWhere('id', $enrollCohortId); @endphp
                @if ($selectedCohort)
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('Start date') }}: <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $selectedCohort->start_date->format('l, F j, Y') }}</span>
                    </flux:text>
                @endif
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Confirm Enrollment') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Set Tuition Modal --}}
    <flux:modal name="set-tuition" class="max-w-md">
        <form wire:submit="setTuition" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Set Tuition') }}</flux:heading>
                <flux:subheading>{{ __('Enter the total tuition amount for this enrollment.') }}</flux:subheading>
            </div>
            <flux:field>
                <flux:label>{{ __('Amount') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="tuitionAmount" type="number" step="0.01" min="0.01" placeholder="0.00" />
                <flux:error name="tuitionAmount" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Add Payment Modal --}}
    <flux:modal name="add-payment" class="max-w-md">
        <form wire:submit="addPayment" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Record Payment') }}</flux:heading>
                <flux:subheading>{{ __('Record a payment received for this enrollment.') }}</flux:subheading>
            </div>
            <flux:field>
                <flux:label>{{ __('Amount') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="paymentAmount" type="number" step="0.01" min="0.01" placeholder="0.00" />
                <flux:error name="paymentAmount" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Method') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:select wire:model="paymentMethod">
                    <flux:select.option value="">{{ __('Select method...') }}</flux:select.option>
                    @foreach (App\Enums\PaymentMethod::cases() as $method)
                        <flux:select.option value="{{ $method->value }}">{{ $method->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="paymentMethod" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Date') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="paymentDate" type="date" />
                <flux:error name="paymentDate" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Notes') }}</flux:label>
                <flux:textarea wire:model="paymentNotes" rows="2" />
                <flux:error name="paymentNotes" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Record Payment') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Add Milestone Modal --}}
    <flux:modal name="add-milestone" class="max-w-md">
        <form wire:submit="addMilestone" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Add Milestone') }}</flux:heading>
                <flux:subheading>{{ __('Track a key program stage or achievement.') }}</flux:subheading>
            </div>
            <flux:field>
                <flux:label>{{ __('Title') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="milestoneTitle" placeholder="{{ __('e.g. Module 1 Complete') }}" />
                <flux:error name="milestoneTitle" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Notes') }}</flux:label>
                <flux:textarea wire:model="milestoneNotes" rows="2" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Add Milestone') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Graduate Student Modal --}}
    <flux:modal name="graduate-student" class="max-w-md">
        <form wire:submit="graduate" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Graduate Student') }}</flux:heading>
                <flux:subheading>{{ __('Record the graduation and issue the certificate.') }}</flux:subheading>
            </div>
            <flux:field>
                <flux:label>{{ __('Graduation Date') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                <flux:input wire:model="graduationDate" type="date" />
                <flux:error name="graduationDate" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Certificate Number') }}</flux:label>
                <flux:input wire:model="certificateNumber" placeholder="{{ __('Optional') }}" />
                <flux:error name="certificateNumber" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Certificate Issued Date') }}</flux:label>
                <flux:input wire:model="certificateIssuedAt" type="date" />
                <flux:error name="certificateIssuedAt" />
            </flux:field>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Confirm Graduation') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left: Prospect Details --}}
        <div class="lg:col-span-2 flex flex-col gap-6">

            {{-- Personal Information --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Personal Information') }}</flux:heading>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Email') }}</flux:text><flux:text>{{ $prospect->email }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Phone') }}</flux:text><flux:text>{{ $prospect->phone }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Date of Birth') }}</flux:text><flux:text>{{ $prospect->date_of_birth?->format('M j, Y') ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Gender') }}</flux:text><flux:text>{{ $prospect->gender ?? '—' }}</flux:text></div>
                    <div class="sm:col-span-2"><flux:text class="text-xs text-zinc-500">{{ __('Address') }}</flux:text><flux:text>{{ $prospect->address ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Emergency Contact') }}</flux:text><flux:text>{{ $prospect->emergency_contact_name ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Emergency Phone') }}</flux:text><flux:text>{{ $prospect->emergency_contact_phone ?? '—' }}</flux:text></div>
                </dl>
            </div>

            {{-- Contact Preferences --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Contact Preferences') }}</flux:heading>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Preferred Method') }}</flux:text><flux:text>{{ $prospect->preferred_contact_method->label() }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Best Time') }}</flux:text><flux:text>{{ $prospect->best_time_to_contact->label() }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Heard About Us') }}</flux:text><flux:text>{{ $prospect->heard_about_us ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Financing Interest') }}</flux:text><flux:text>{{ $prospect->financing_interest ? __('Yes') : __('No') }}</flux:text></div>
                </dl>
            </div>

            {{-- Background --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Background') }}</flux:heading>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Cohort') }}</flux:text><flux:text>{{ $prospect->cohort ? $prospect->cohort->name.' — '.$prospect->cohort->start_date->format('M j, Y') : '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Education Level') }}</flux:text><flux:text>{{ $prospect->highest_education_level?->label() ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Employment Status') }}</flux:text><flux:text>{{ $prospect->employment_status?->label() ?? '—' }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Entry Point') }}</flux:text><flux:text>{{ $prospect->entry_point->label() }}</flux:text></div>
                    @if ($prospect->goals)
                        <div class="sm:col-span-2">
                            <flux:text class="text-xs text-zinc-500">{{ __('Goals') }}</flux:text>
                            <flux:text>{{ $prospect->goals }}</flux:text>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Log Activity --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Log Activity') }}</flux:heading>
                <form wire:submit="logActivity" class="flex flex-col gap-4">
                    <flux:field>
                        <flux:label>{{ __('Type') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                        <flux:select wire:model="activityAction">
                            <flux:select.option value="">{{ __('Select activity type...') }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::Call->value }}">{{ App\Enums\ActivityAction::Call->label() }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::Email->value }}">{{ App\Enums\ActivityAction::Email->label() }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::Text->value }}">{{ App\Enums\ActivityAction::Text->label() }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::WalkIn->value }}">{{ App\Enums\ActivityAction::WalkIn->label() }}</flux:select.option>
                            <flux:select.option value="{{ App\Enums\ActivityAction::Note->value }}">{{ App\Enums\ActivityAction::Note->label() }}</flux:select.option>
                        </flux:select>
                        <flux:error name="activityAction" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Notes') }}</flux:label>
                        <flux:textarea wire:model="activityNotes" rows="3" placeholder="{{ __('What happened during this interaction?') }}" />
                        <flux:error name="activityNotes" />
                    </flux:field>
                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">{{ __('Log Activity') }}</flux:button>
                    </div>
                </form>
            </div>

            {{-- Activity Timeline --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Activity Timeline') }}</flux:heading>
                <div class="flex flex-col gap-3">
                    @forelse ($prospect->activities as $activity)
                        <div wire:key="{{ $activity->id }}" class="flex gap-3 text-sm">
                            <div class="flex flex-col items-center">
                                <div class="flex size-7 items-center justify-center rounded-full {{ $activity->type === App\Enums\ActivityType::Automatic ? 'bg-zinc-100 dark:bg-zinc-700' : 'bg-blue-100 dark:bg-blue-900' }}">
                                    <flux:icon name="{{ $activity->type === App\Enums\ActivityType::Automatic ? 'arrow-path' : 'chat-bubble-left' }}" class="size-3.5 {{ $activity->type === App\Enums\ActivityType::Automatic ? 'text-zinc-500' : 'text-blue-600' }}" />
                                </div>
                                <div class="mt-1 flex-1 border-l border-zinc-200 dark:border-zinc-700"></div>
                            </div>
                            <div class="pb-4 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $activity->action->label() }}</span>
                                    @if ($activity->performedBy)
                                        <span class="text-zinc-500">{{ __('by') }} {{ $activity->performedBy->name }}</span>
                                    @endif
                                    <span class="text-zinc-400 text-xs ml-auto">{{ $activity->created_at->format('M j, Y g:i a') }}</span>
                                </div>
                                @if ($activity->from_value && $activity->to_value)
                                    <flux:text class="text-zinc-500 text-sm mt-1">
                                        {{ $activity->from_value }} → {{ $activity->to_value }}
                                    </flux:text>
                                @endif
                                @if ($activity->notes)
                                    <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $activity->notes }}</flux:text>
                                @endif
                            </div>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500">{{ __('No activity recorded yet.') }}</flux:text>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex flex-col gap-6">
            {{-- Status --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Status') }}</flux:heading>
                <form wire:submit="updateStatus" class="flex flex-col gap-3">
                    <flux:select wire:model="newStatus">
                        @foreach (ProspectStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:button type="submit" variant="primary" class="w-full">{{ __('Update Status') }}</flux:button>
                </form>
            </div>

            {{-- Enrollment & Payments --}}
            @if ($prospect->enrollment)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:heading class="mb-4">{{ __('Enrollment') }}</flux:heading>
                    <dl class="flex flex-col gap-2 mb-4">
                        <div>
                            <flux:text class="text-xs text-zinc-500">{{ __('Status') }}</flux:text>
                            <flux:badge color="{{ $prospect->enrollment->status->color() }}" size="sm">{{ $prospect->enrollment->status->label() }}</flux:badge>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">{{ __('Tuition') }}</flux:text>
                            <flux:text class="text-sm">{{ $prospect->enrollment->amount_owed !== null ? '$'.number_format($prospect->enrollment->amount_owed, 2) : __('Not set') }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">{{ __('Paid') }}</flux:text>
                            <flux:text class="text-sm">${{ number_format($prospect->enrollment->totalPaid(), 2) }}</flux:text>
                        </div>
                        @if ($prospect->enrollment->amount_owed !== null)
                            <div>
                                <flux:text class="text-xs text-zinc-500">{{ __('Balance') }}</flux:text>
                                <flux:text class="text-sm">${{ number_format($prospect->enrollment->balance(), 2) }}</flux:text>
                            </div>
                        @endif
                    </dl>
                    @if (auth()->user()->isAdmin())
                        <div class="flex flex-col gap-2">
                            @can('setTuition', $prospect->enrollment)
                                <flux:modal.trigger name="set-tuition">
                                    <flux:button size="sm" class="w-full">
                                        {{ $prospect->enrollment->amount_owed !== null ? __('Update Tuition') : __('Set Tuition') }}
                                    </flux:button>
                                </flux:modal.trigger>
                            @endcan
                            @can('createPayment', $prospect->enrollment)
                                <flux:modal.trigger name="add-payment">
                                    <flux:button size="sm" variant="primary" class="w-full" :disabled="$prospect->enrollment->amount_owed === null">
                                        {{ __('Add Payment') }}
                                    </flux:button>
                                </flux:modal.trigger>
                            @endcan
                        </div>
                    @endif
                    @if ($prospect->enrollment->payments->isNotEmpty())
                        <div class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                            <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-2">{{ __('Payment History') }}</flux:text>
                            <div class="flex flex-col gap-2">
                                @foreach ($prospect->enrollment->payments->sortByDesc('paid_at') as $payment)
                                    <div wire:key="{{ $payment->id }}" class="flex items-center justify-between text-sm">
                                        <div>
                                            <flux:text class="font-medium">${{ number_format($payment->amount, 2) }}</flux:text>
                                            <flux:text class="text-xs text-zinc-500">{{ $payment->method->label() }} · {{ $payment->paid_at->format('M j, Y') }}</flux:text>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Attendance --}}
                    <div class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <div class="flex items-center justify-between mb-2">
                            <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Attendance') }}</flux:text>
                            @if (auth()->user()->isAdmin())
                                <flux:button size="xs" :href="route('cohorts.attendance', $prospect->enrollment->cohort_id)" wire:navigate>{{ __('Record') }}</flux:button>
                            @endif
                        </div>
                        @if ($prospect->enrollment->attendanceRecords->isNotEmpty())
                            <flux:text class="text-xs text-zinc-500 mb-2">{{ trans_choice(':count session|:count sessions', $prospect->enrollment->attendanceRecords->count()) }}</flux:text>
                            <div class="flex items-center gap-2 flex-wrap">
                                @foreach ($prospect->enrollment->attendanceRecords->groupBy(fn ($r) => $r->status->value) as $status => $records)
                                    @php $statusEnum = App\Enums\AttendanceStatus::from($status); @endphp
                                    <flux:badge color="{{ $statusEnum->color() }}" size="sm">{{ $records->count() }} {{ $statusEnum->label() }}</flux:badge>
                                @endforeach
                            </div>
                        @else
                            <flux:text class="text-xs text-zinc-400">{{ __('No sessions recorded.') }}</flux:text>
                        @endif
                    </div>

                    {{-- Milestones --}}
                    <div class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <div class="flex items-center justify-between mb-2">
                            <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('Milestones') }}</flux:text>
                            @if (auth()->user()->isAdmin())
                                <flux:modal.trigger name="add-milestone">
                                    <flux:button size="xs" icon="plus">{{ __('Add') }}</flux:button>
                                </flux:modal.trigger>
                            @endif
                        </div>
                        @if ($prospect->enrollment->milestoneRecords->isNotEmpty())
                            <div class="flex flex-col gap-2">
                                @foreach ($prospect->enrollment->milestoneRecords as $milestone)
                                    <div wire:key="milestone-{{ $milestone->id }}" class="flex items-center justify-between gap-2 text-sm">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            @if ($milestone->completed_at)
                                                <flux:icon name="check-circle" class="size-4 text-green-500 shrink-0" />
                                            @else
                                                <flux:icon name="clock" class="size-4 text-zinc-400 shrink-0" />
                                            @endif
                                            <flux:text class="text-sm truncate">{{ $milestone->title }}</flux:text>
                                        </div>
                                        @if (!$milestone->completed_at && auth()->user()->isAdmin())
                                            <flux:button size="xs" wire:click="completeMilestone({{ $milestone->id }})">{{ __('Done') }}</flux:button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <flux:text class="text-xs text-zinc-400">{{ __('No milestones added.') }}</flux:text>
                        @endif
                    </div>

                    {{-- Graduation --}}
                    <div class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-2">{{ __('Graduation') }}</flux:text>
                        @if ($prospect->enrollment->isGraduated())
                            <dl class="flex flex-col gap-1">
                                <div>
                                    <flux:text class="text-xs text-zinc-500">{{ __('Graduated') }}</flux:text>
                                    <flux:text class="text-sm">{{ $prospect->enrollment->graduated_at->format('M j, Y') }}</flux:text>
                                </div>
                                @if ($prospect->enrollment->certificate_number)
                                    <div>
                                        <flux:text class="text-xs text-zinc-500">{{ __('Certificate #') }}</flux:text>
                                        <flux:text class="text-sm">{{ $prospect->enrollment->certificate_number }}</flux:text>
                                    </div>
                                @endif
                                @if ($prospect->enrollment->certificate_issued_at)
                                    <div>
                                        <flux:text class="text-xs text-zinc-500">{{ __('Issued') }}</flux:text>
                                        <flux:text class="text-sm">{{ $prospect->enrollment->certificate_issued_at->format('M j, Y') }}</flux:text>
                                    </div>
                                @endif
                            </dl>
                        @elseif (auth()->user()->isAdmin())
                            <flux:modal.trigger name="graduate-student">
                                <flux:button size="sm" variant="primary" class="w-full">{{ __('Graduate Student') }}</flux:button>
                            </flux:modal.trigger>
                        @else
                            <flux:text class="text-xs text-zinc-400">{{ __('Not yet graduated.') }}</flux:text>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Assignment (admin only) --}}
            @if (auth()->user()->isAdmin())
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:heading class="mb-4">{{ __('Assignment') }}</flux:heading>
                    <form wire:submit="updateAssignment" class="flex flex-col gap-3">
                        <flux:select wire:model="newAssignedTo">
                            <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                            @foreach ($this->staffMembers as $staff)
                                <flux:select.option value="{{ $staff->id }}">{{ $staff->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button type="submit" variant="primary" class="w-full">{{ __('Reassign') }}</flux:button>
                    </form>
                </div>
            @endif

            {{-- Meta --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <flux:heading class="mb-4">{{ __('Details') }}</flux:heading>
                <dl class="flex flex-col gap-2">
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Submitted') }}</flux:text><flux:text class="text-sm">{{ $prospect->created_at->format('M j, Y g:i a') }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Last Updated') }}</flux:text><flux:text class="text-sm">{{ $prospect->updated_at->format('M j, Y g:i a') }}</flux:text></div>
                    <div><flux:text class="text-xs text-zinc-500">{{ __('Assigned To') }}</flux:text><flux:text class="text-sm">{{ $prospect->assignedTo?->name ?? '—' }}</flux:text></div>
                </dl>
            </div>
        </div>
    </div>
</div>
