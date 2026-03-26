<?php

namespace App\Enums;

enum DocumentType: string
{
    case EnrollmentAgreement = 'enrollment_agreement';
    case StudentId = 'student_id';
    case Certificate = 'certificate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EnrollmentAgreement => 'Enrollment Agreement',
            self::StudentId => 'Student ID',
            self::Certificate => 'Certificate',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EnrollmentAgreement => 'blue',
            self::StudentId => 'purple',
            self::Certificate => 'green',
            self::Other => 'zinc',
        };
    }
}
