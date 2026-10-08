<?php

namespace App\Enums;

enum ProductCondition: string
{
    case New = 'new';
    case GradeA = 'grade_a';
    case GradeB = 'grade_b';
    case GradeC = 'grade_c';
    case Refurbished = 'refurbished';

    public function label(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::GradeA => __('Grade A'),
            self::GradeB => __('Grade B'),
            self::GradeC => __('Grade C'),
            self::Refurbished => __('Refurbished'),
        };
    }
}
