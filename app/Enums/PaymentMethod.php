<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'Cash';
    case Check = 'Check';
    case Card = 'Card';
    case BankTransfer = 'BankTransfer';
    case Other = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Check => 'Check',
            self::Card => 'Card',
            self::BankTransfer => 'Bank Transfer',
            self::Other => 'Other',
        };
    }
}
