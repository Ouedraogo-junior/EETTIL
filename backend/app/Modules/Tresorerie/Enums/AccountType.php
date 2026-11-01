<?php

namespace App\Modules\Tresorerie\Enums;

enum AccountType: string
{
    case Caisse = 'caisse';
    case Banque = 'banque';

    public function label(): string
    {
        return match ($this) {
            self::Caisse => 'Caisse',
            self::Banque => 'Banque',
        };
    }
}