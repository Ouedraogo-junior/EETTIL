<?php

namespace App\Modules\Facturation\Domain;

/** Une ligne saisie sur un document : bloc A (débours) ou B (prestations). */
final readonly class InvoiceLine
{
    public const DEBOURS = 'D';
    public const PRESTATION = 'P';

    public const DISCOUNT_PERCENT = 'pct';
    public const DISCOUNT_AMOUNT = 'amt';

    /**
     * @param  string  $block  'D' (débours) ou 'P' (prestations)
     * @param  ?string  $discountKind  'pct', 'amt' ou null (prestations seulement)
     */
    public function __construct(
        public string $block,
        public string $label,
        public string $unit,
        public float $quantity,
        public int $unitPrice,
        public ?string $discountKind = null,
        public float $discountValue = 0,
    ) {}

    public function isDebours(): bool
    {
        return $this->block === self::DEBOURS;
    }
}