<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Received => 'Received',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Received => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }
}
