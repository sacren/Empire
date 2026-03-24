<?php

namespace App\Console\Commands;

use App\Enums\EnrollmentStatus;
use App\Enums\ProspectStatus;
use App\Mail\TuitionReminder;
use App\Models\Enrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTuitionReminders extends Command
{
    protected $signature = 'app:send-tuition-reminders';

    protected $description = 'Send monthly tuition reminders to students with outstanding balances';

    public function handle(): int
    {
        $enrollments = Enrollment::query()
            ->whereNot('status', EnrollmentStatus::Paid)
            ->whereNotNull('amount_owed')
            ->where('amount_owed', '>', 0)
            ->whereHas('prospect', fn ($q) => $q->whereIn('status', [
                ProspectStatus::Enrolled,
                ProspectStatus::Graduated,
            ]))
            ->with(['prospect', 'cohort'])
            ->get();

        foreach ($enrollments as $enrollment) {
            Mail::to($enrollment->prospect->email)
                ->send(new TuitionReminder($enrollment));
        }

        $count = $enrollments->count();

        $this->info("Sent {$count} tuition reminder(s).");

        return Command::SUCCESS;
    }
}
