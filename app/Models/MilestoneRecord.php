<?php

namespace App\Models;

use Database\Factories\MilestoneRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestoneRecord extends Model
{
    /** @use HasFactory<MilestoneRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'title',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
