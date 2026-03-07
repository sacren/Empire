<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Employed = 'employed';
    case Unemployed = 'unemployed';
    case SelfEmployed = 'self_employed';
    case Student = 'student';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Employed => 'Employed',
            self::Unemployed => 'Unemployed',
            self::SelfEmployed => 'Self-Employed',
            self::Student => 'Student',
            self::Other => 'Other',
        };
    }
}
