<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'prospect_id',
        'cohort_id',
        'amount_owed',
        'status',
        'enrolled_at',
        'graduated_at',
        'certificate_number',
        'certificate_issued_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_owed' => 'decimal:2',
            'enrolled_at' => 'datetime',
            'graduated_at' => 'datetime',
            'certificate_issued_at' => 'date',
            'status' => EnrollmentStatus::class,
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class)->latest('session_date');
    }

    public function milestoneRecords(): HasMany
    {
        return $this->hasMany(MilestoneRecord::class)->oldest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->latest();
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balance(): ?float
    {
        if ($this->amount_owed === null) {
            return null;
        }

        return (float) $this->amount_owed - $this->totalPaid();
    }

    public function isGraduated(): bool
    {
        return $this->graduated_at !== null;
    }

    public function recalculateStatus(): void
    {
        $totalPaid = $this->totalPaid();

        if ($this->amount_owed === null) {
            $this->status = EnrollmentStatus::Pending;
        } elseif ($totalPaid >= (float) $this->amount_owed) {
            $this->status = EnrollmentStatus::Paid;
        } elseif ($totalPaid > 0) {
            $this->status = EnrollmentStatus::Partial;
        } else {
            $this->status = EnrollmentStatus::Pending;
        }

        $this->save();
    }
}
