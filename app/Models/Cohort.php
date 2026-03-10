<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cohort extends Model
{
    /** @use HasFactory<\Database\Factories\CohortFactory> */
    use HasFactory;

    protected $fillable = [
        'program_id',
        'name',
        'start_date',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(Prospect::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
