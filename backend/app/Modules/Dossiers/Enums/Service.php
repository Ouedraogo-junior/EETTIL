<?php

namespace App\Modules\Dossiers\Enums;

enum Service: string
{
    case Transit = 'transit';
    case Transport = 'transport';
    case Dedouanement = 'dedouanement';
    case Manutention = 'manutention';
    case Conseil = 'conseil';

    public function label(): string
    {
        return match ($this) {
            self::Transit => 'Transit',
            self::Transport => 'Transport',
            self::Dedouanement => 'Dédouanement',
            self::Manutention => 'Manutention',
            self::Conseil => 'Conseil logistique',
        };
    }
}