<?php

namespace App\Enums;

enum ProspectStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case InProgress = 'in_progress';
    case Qualified = 'qualified';
    case Enrolled = 'enrolled';
    case Disqualified = 'disqualified';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::InProgress => 'In Progress',
            self::Qualified => 'Qualified',
            self::Enrolled => 'Enrolled',
            self::Disqualified => 'Disqualified',
            self::Abandoned => 'Abandoned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Contacted => 'yellow',
            self::InProgress => 'orange',
            self::Qualified => 'green',
            self::Enrolled => 'lime',
            self::Disqualified => 'red',
            self::Abandoned => 'zinc',
        };
    }
}
