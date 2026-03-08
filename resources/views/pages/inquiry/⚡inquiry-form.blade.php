<?php

use App\Enums\ContactMethod;
use App\Enums\ContactTime;
use App\Enums\EducationLevel;
use App\Enums\EmploymentStatus;
use App\Enums\ProspectEntryPoint;
use App\Enums\ProspectStatus;
use App\Models\Cohort;
use App\Models\Program;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Services\RoundRobinAssignmentService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Enums\ActivityAction;
use App\Enums\ActivityType;

new #[Title('Apply Now')] #[Layout('layouts.public')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $date_of_birth = '';
    public string $gender = '';
    public string $emergency_contact_name = '';
    public string $emergency_contact_phone = '';
    public string $preferred_contact_method = '';
    public string $best_time_to_contact = '';
    public string $heard_about_us = '';
    public string $cohort_id = '';
    public string $highest_education_level = '';
    public string $employment_status = '';
    public bool $financing_interest = false;
    public string $goals = '';
    public bool $submitted = false;

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'preferred_contact_method' => ['required', 'string'],
            'best_time_to_contact' => ['required', 'string'],
            'heard_about_us' => ['nullable', 'string', 'max:255'],
            'cohort_id' => ['nullable', 'exists:cohorts,id'],
            'highest_education_level' => ['nullable', 'string'],
            'employment_status' => ['nullable', 'string'],
            'financing_interest' => ['boolean'],
            'goals' => ['nullable', 'string', 'max:2000'],
        ]);

        $assignedUser = app(RoundRobinAssignmentService::class)->nextStaffMember();

        $prospect = Prospect::create([
            ...$validated,
            'status' => ProspectStatus::New,
            'entry_point' => ProspectEntryPoint::PublicForm,
            'assigned_to' => $assignedUser?->id,
        ]);

        ProspectActivity::create([
            'prospect_id' => $prospect->id,
            'type' => ActivityType::Automatic,
            'action' => ActivityAction::FormSubmission,
            'notes' => 'Inquiry submitted via public form.',
            'performed_by' => null,
        ]);

        $this->submitted = true;
    }
}; ?>

<div>
    @if ($submitted)
        <div class="flex flex-col items-center gap-4 py-8 text-center">
            <flux:icon name="check-circle" class="size-16 text-green-500" />
            <flux:heading size="xl">{{ __('Thank you for your interest!') }}</flux:heading>
            <flux:text>{{ __('We have received your inquiry and a member of our team will be in touch with you shortly.') }}</flux:text>
        </div>
    @else
        <div class="mb-6 text-center">
            <flux:heading size="xl">{{ __('Apply to :program', ['program' => 'SkillPath']) }}</flux:heading>
            <flux:text class="mt-1">{{ __('Fill out the form below and we will get back to you soon.') }}</flux:text>
        </div>

        <form wire:submit="submit" class="flex flex-col gap-5">
            {{-- Personal Information --}}
            <flux:heading size="lg">{{ __('Personal Information') }}</flux:heading>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Full Name') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                    <flux:input wire:model="name" type="text" autocomplete="name" placeholder="{{ __('John Smith') }}" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Date of Birth') }}</flux:label>
                    <flux:input wire:model="date_of_birth" type="date" />
                    <flux:error name="date_of_birth" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Email Address') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                    <flux:input wire:model="email" type="email" autocomplete="email" placeholder="email@example.com" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Phone Number') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                    <flux:input wire:model="phone" type="tel" placeholder="(555) 000-0000" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('Address') }}</flux:label>
                    <flux:input wire:model="address" type="text" placeholder="{{ __('123 Main St, City, State') }}" />
                    <flux:error name="address" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Gender') }}</flux:label>
                    <flux:select wire:model="gender">
                        <flux:select.option value="">{{ __('Prefer not to say') }}</flux:select.option>
                        <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                        <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                        <flux:select.option value="non_binary">{{ __('Non-binary') }}</flux:select.option>
                        <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="gender" />
                </flux:field>
            </div>

            <flux:separator />

            {{-- Emergency Contact --}}
            <flux:heading size="lg">{{ __('Emergency Contact') }}</flux:heading>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Emergency Contact Name') }}</flux:label>
                    <flux:input wire:model="emergency_contact_name" type="text" />
                    <flux:error name="emergency_contact_name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Emergency Contact Phone') }}</flux:label>
                    <flux:input wire:model="emergency_contact_phone" type="tel" />
                    <flux:error name="emergency_contact_phone" />
                </flux:field>
            </div>

            <flux:separator />

            {{-- Contact Preferences --}}
            <flux:heading size="lg">{{ __('Contact Preferences') }}</flux:heading>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Preferred Contact Method') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                    <flux:select wire:model="preferred_contact_method">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach (ContactMethod::cases() as $method)
                            <flux:select.option value="{{ $method->value }}">{{ $method->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="preferred_contact_method" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Best Time to Contact') }} <span class="text-red-400 ms-0.5" aria-hidden="true">*</span></flux:label>
                    <flux:select wire:model="best_time_to_contact">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach (ContactTime::cases() as $time)
                            <flux:select.option value="{{ $time->value }}">{{ $time->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="best_time_to_contact" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('How did you hear about us?') }}</flux:label>
                    <flux:select wire:model="heard_about_us">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        <flux:select.option value="internet_search">{{ __('Internet Search') }}</flux:select.option>
                        <flux:select.option value="social_media">{{ __('Social Media') }}</flux:select.option>
                        <flux:select.option value="friend_family">{{ __('Friend or Family') }}</flux:select.option>
                        <flux:select.option value="advertisement">{{ __('Advertisement') }}</flux:select.option>
                        <flux:select.option value="job_fair">{{ __('Job Fair') }}</flux:select.option>
                        <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="heard_about_us" />
                </flux:field>
            </div>

            <flux:separator />

            {{-- Program & Background --}}
            <flux:heading size="lg">{{ __('Program & Background') }}</flux:heading>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Preferred Start Date') }}</flux:label>
                    <flux:select wire:model="cohort_id">
                        <flux:select.option value="">{{ __('No preference') }}</flux:select.option>
                        @foreach (App\Models\Program::where('is_active', true)->with('activeCohorts')->get() as $program)
                            @foreach ($program->activeCohorts as $cohort)
                                <flux:select.option value="{{ $cohort->id }}">{{ $cohort->name }} — {{ $cohort->start_date->format('M j, Y') }}</flux:select.option>
                            @endforeach
                        @endforeach
                    </flux:select>
                    <flux:error name="cohort_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Highest Education Level') }}</flux:label>
                    <flux:select wire:model="highest_education_level">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach (EducationLevel::cases() as $level)
                            <flux:select.option value="{{ $level->value }}">{{ $level->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="highest_education_level" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Employment Status') }}</flux:label>
                    <flux:select wire:model="employment_status">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach (EmploymentStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="employment_status" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Interested in financial aid or payment plans?') }}</flux:label>
                    <flux:checkbox wire:model="financing_interest" :label="__('Yes, I am interested in financing options')" />
                    <flux:error name="financing_interest" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Tell us about your goals for joining this program') }}</flux:label>
                <flux:textarea wire:model="goals" rows="4" placeholder="{{ __('Share your goals, motivation, and what you hope to achieve...') }}" />
                <flux:error name="goals" />
            </flux:field>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                    <span wire:loading.remove wire:target="submit">{{ __('Submit Inquiry') }}</span>
                    <span wire:loading wire:target="submit">{{ __('Submitting...') }}</span>
                </flux:button>
            </div>
        </form>
    @endif
</div>
