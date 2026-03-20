<?php

namespace App\Enums;

enum CommunicationChannel: string
{
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
        };
    }
}
