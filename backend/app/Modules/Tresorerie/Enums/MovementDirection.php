<?php

namespace App\Modules\Tresorerie\Enums;

enum MovementDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Entrée',
            self::Out => 'Sortie',
        };
    }
}