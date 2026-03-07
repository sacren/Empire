<?php

namespace App\Enums;

enum ContactMethod: string
{
    case Phone = 'phone';
    case Email = 'email';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Phone',
            self::Email => 'Email',
            self::Text => 'Text',
        };
    }
}
