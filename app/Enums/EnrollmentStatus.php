<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Partial => 'Partial Payment',
            self::Paid => 'Paid in Full',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::Partial => 'yellow',
            self::Paid => 'green',
        };
    }
}
