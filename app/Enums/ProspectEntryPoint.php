<?php

namespace App\Enums;

enum ProspectEntryPoint: string
{
    case PublicForm = 'public_form';
    case StaffEntered = 'staff_entered';

    public function label(): string
    {
        return match ($this) {
            self::PublicForm => 'Public Form',
            self::StaffEntered => 'Staff Entered',
        };
    }
}
