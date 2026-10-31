<?php

namespace App\Modules\Facturation\Enums;

enum DocumentType: string
{
    case Proforma = 'proforma';
    case Facture = 'facture';

    public function label(): string
    {
        return match ($this) {
            self::Proforma => 'Quotation',
            self::Facture => 'Facture',
        };
    }
}