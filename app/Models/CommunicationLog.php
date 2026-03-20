<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    /** @use HasFactory<\Database\Factories\CommunicationLogFactory> */
    use HasFactory;

    protected $fillable = [
        'prospect_id',
        'sent_by',
        'channel',
        'type',
        'subject',
        'body',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => CommunicationChannel::class,
            'type' => CommunicationType::class,
            'sent_at' => 'datetime',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
