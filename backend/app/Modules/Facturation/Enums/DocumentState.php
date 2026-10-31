<?php

namespace App\Modules\Facturation\Enums;

enum DocumentState: string
{
    case Emis = 'emis';
    case Annule = 'annule';
    case Transforme = 'transforme'; // proforma devenue facture

    public function label(): string
    {
        return match ($this) {
            self::Emis => 'Émis',
            self::Annule => 'Annulé',
            self::Transforme => 'Transformée',
        };
    }
}