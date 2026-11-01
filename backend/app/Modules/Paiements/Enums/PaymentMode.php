<?php

namespace App\Modules\Paiements\Enums;

use App\Modules\Tresorerie\Enums\AccountType;

enum PaymentMode: string
{
    case Especes = 'especes';
    case Virement = 'virement';
    case MobileMoney = 'mobile_money';

    public function label(): string
    {
        return match ($this) {
            self::Especes => 'Espèces',
            self::Virement => 'Virement',
            self::MobileMoney => 'Mobile money',
        };
    }

    /** Type de compte habituellement crédité : sert à présélectionner le compte à l'écran. */
    public function usualAccountType(): AccountType
    {
        return match ($this) {
            self::Virement => AccountType::Banque,
            default => AccountType::Caisse,
        };
    }
}