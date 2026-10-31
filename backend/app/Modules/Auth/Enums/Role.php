<?php

namespace App\Modules\Auth\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Gerant = 'gerant';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Gerant => 'Gérant',
        };
    }
}