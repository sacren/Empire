<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'prospect_id',
        'cohort_id',
        'amount_owed',
        'status',
        'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_owed' => 'decimal:2',
            'enrolled_at' => 'datetime',
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

    public function recalculateStatus(): void
    {
        if ($this->amount_owed === null) {
            $this->status = EnrollmentStatus::Pending;
        } elseif ($this->totalPaid() >= (float) $this->amount_owed) {
            $this->status = EnrollmentStatus::Paid;
        } elseif ($this->totalPaid() > 0) {
            $this->status = EnrollmentStatus::Partial;
        } else {
            $this->status = EnrollmentStatus::Pending;
        }

        $this->save();
    }
}
