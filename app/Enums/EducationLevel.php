<?php

namespace App\Enums;

enum EducationLevel: string
{
    case HighSchoolDiploma = 'high_school_diploma';
    case Ged = 'ged';
    case SomeCollege = 'some_college';
    case AssociatesDegree = 'associates_degree';
    case BachelorsDegree = 'bachelors_degree';
    case Graduate = 'graduate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HighSchoolDiploma => 'High School Diploma',
            self::Ged => 'GED',
            self::SomeCollege => 'Some College',
            self::AssociatesDegree => "Associate's Degree",
            self::BachelorsDegree => "Bachelor's Degree",
            self::Graduate => 'Graduate Degree',
            self::Other => 'Other',
        };
    }
}
