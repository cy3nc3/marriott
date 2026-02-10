<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case REGISTRAR = 'registrar';
    case FINANCE = 'finance';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case PARENT = 'parent';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::REGISTRAR => 'Registrar',
            self::FINANCE => 'Finance',
            self::TEACHER => 'Teacher',
            self::STUDENT => 'Student',
            self::PARENT => 'Parent',
        };
    }
}
