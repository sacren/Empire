<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'default_tuition',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_tuition' => 'decimal:2',
        ];
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class);
    }

    public function activeCohorts(): HasMany
    {
        return $this->hasMany(Cohort::class)->where('is_active', true)->orderBy('start_date');
    }
}
