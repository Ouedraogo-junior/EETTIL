<?php

namespace App\Modules\Facturation\Domain;

/** Résultat du calcul d'une ligne. */
final readonly class LineTotals
{
    public function __construct(
        public int $index,
        public int $gross,      // quantité × prix unitaire
        public int $discount,   // remise appliquée (0 pour un débours)
        public bool $overLimit, // la remise saisie dépassait le montant de la ligne (elle a été plafonnée)
        public int $total,      // gross - discount
    ) {}

    /** @return array<string,int|bool> */
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'gross' => $this->gross,
            'discount' => $this->discount,
            'over_limit' => $this->overLimit,
            'total' => $this->total,
        ];
    }
}