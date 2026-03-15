<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::Late => 'Late',
            self::Excused => 'Excused',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'green',
            self::Absent => 'red',
            self::Late => 'yellow',
            self::Excused => 'zinc',
        };
    }
}
