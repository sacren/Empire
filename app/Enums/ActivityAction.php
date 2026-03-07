<?php

namespace App\Enums;

enum ActivityAction: string
{
    case Call = 'call';
    case Email = 'email';
    case Text = 'text';
    case WalkIn = 'walk_in';
    case Note = 'note';
    case StatusChange = 'status_change';
    case AssignmentChange = 'assignment_change';
    case FormSubmission = 'form_submission';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Phone Call',
            self::Email => 'Email',
            self::Text => 'Text Message',
            self::WalkIn => 'Walk-in',
            self::Note => 'Note',
            self::StatusChange => 'Status Change',
            self::AssignmentChange => 'Assignment Change',
            self::FormSubmission => 'Form Submission',
        };
    }
}
