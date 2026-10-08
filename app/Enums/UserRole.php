<?php

namespace App\Enums;

enum UserRole: string
{
    /** Platform operator: creates and manages companies. Has no company_id. */
    case SuperAdmin = 'superadmin';
    case Owner = 'owner';
    case Manager = 'manager';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super admin'),
            self::Owner => __('Owner'),
            self::Manager => __('Manager'),
            self::Staff => __('Staff'),
        };
    }

    /** Roles a company owner can give to their own users. */
    public static function companyRoles(): array
    {
        return [self::Owner, self::Manager, self::Staff];
    }
}
