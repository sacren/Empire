<?php

namespace App\Enums;

enum CommunicationType: string
{
    case Automated = 'automated';
    case Manual = 'manual';
    case Bulk = 'bulk';

    public function label(): string
    {
        return match ($this) {
            self::Automated => 'Automated',
            self::Manual => 'Manual',
            self::Bulk => 'Bulk',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Automated => 'zinc',
            self::Manual => 'blue',
            self::Bulk => 'purple',
        };
    }
}
