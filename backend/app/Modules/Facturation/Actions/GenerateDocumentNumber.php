<?php

namespace App\Modules\Facturation\Actions;

use App\Modules\Facturation\Enums\DocumentType;
use App\Support\Counter;
use App\Support\Settings;
use Carbon\CarbonInterface;

/** Numéro de document : FCT/2026-000-012. Un compteur par type et par année, jamais réutilisé. */
class GenerateDocumentNumber
{
    public function handle(DocumentType $type, CarbonInterface $date): string
    {
        $year = $date->year;
        $sequence = Counter::next("{$type->value}-{$year}");
        $prefix = Settings::get($type === DocumentType::Facture ? 'invoice_prefix' : 'proforma_prefix');

        return sprintf('%s/%d-%s-%03d', $prefix, $year, Settings::get('series'), $sequence);
    }
}