<?php

namespace App\Enums;

enum SpecFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Select = 'select';
    case Boolean = 'boolean';

    public function label(): string
    {
        return match ($this) {
            self::Text => __('Text'),
            self::Number => __('Number'),
            self::Select => __('Dropdown'),
            self::Boolean => __('Yes / No'),
        };
    }
}
