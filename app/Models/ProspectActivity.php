<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectActivity extends Model
{
    protected $fillable = [
        'prospect_id',
        'type',
        'action',
        'notes',
        'from_value',
        'to_value',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'action' => ActivityAction::class,
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
