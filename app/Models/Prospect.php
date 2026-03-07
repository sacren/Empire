<?php

namespace App\Models;

use App\Enums\ContactMethod;
use App\Enums\ContactTime;
use App\Enums\EducationLevel;
use App\Enums\EmploymentStatus;
use App\Enums\ProspectEntryPoint;
use App\Enums\ProspectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prospect extends Model
{
    /** @use HasFactory<\Database\Factories\ProspectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'emergency_contact_name',
        'emergency_contact_phone',
        'preferred_contact_method',
        'best_time_to_contact',
        'heard_about_us',
        'cohort_id',
        'highest_education_level',
        'employment_status',
        'financing_interest',
        'goals',
        'status',
        'assigned_to',
        'entry_point',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'financing_interest' => 'boolean',
            'status' => ProspectStatus::class,
            'preferred_contact_method' => ContactMethod::class,
            'best_time_to_contact' => ContactTime::class,
            'highest_education_level' => EducationLevel::class,
            'employment_status' => EmploymentStatus::class,
            'entry_point' => ProspectEntryPoint::class,
        ];
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProspectActivity::class)->latest();
    }
}
