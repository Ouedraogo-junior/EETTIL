<?php

namespace App\Modules\Suivi\Actions;

use App\Modules\Suivi\Models\DossierTracking;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use OpenSpout\Common\Entity\Cell;

/** Exporte le suivi vers un classeur Excel, dans l'ordre des 16 informations du fichier actuel. */
class ExportSuivi
{
    /** Largeurs des colonnes, de la 1re à la dernière. */
    private const WIDTHS = [24, 22, 20, 14, 18, 12, 30, 10, 12, 16, 16, 16, 16, 16, 16, 40, 28, 26];

    public function stream(Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $options = new Options();
            foreach (self::WIDTHS as $i => $width) {
                $options->setColumnWidth($width, $i + 1);
            }

            $writer = new Writer($options);
            $writer->openToFile('php://output');
            $bold = (new Style())->withFontBold(true);
            $writer->addRow(new Row(array_map(
                fn (string $header) => Cell::fromValue($header, $bold),
                $this->headers(),
            )));
            foreach ($query->get() as $tracking) {
                $writer->addRow(Row::fromValues($this->row($tracking)));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** @return list<string> */
    private function headers(): array
    {
        return array_merge(
            [
                'Facture(s) fournisseur', 'N° de DPI', 'BL / LTA', 'N° dossier EETTIL', 'N° d’ordre de transit',
                'Régime douanier', 'Nature de la marchandise', 'Nombre de colis', 'Poids (kg)',
            ],
            array_values(DossierTracking::STEPS),
            ['Commentaires', 'Client', 'Étape actuelle'],
        );
    }

    /** @return list<string|int|float> */
    private function row(DossierTracking $t): array
    {
        // « C8811 DU 29/11/23 », comme dans le fichier actuel
        $dpi = $t->dpi_number
            ? mb_strtoupper($t->dpi_number).($t->dpi_date ? ' DU '.$t->dpi_date->format('d/m/y') : '')
            : '';

        $invoices = collect(explode(';', (string) $t->supplier_invoices))
            ->map(fn (string $s) => trim($s))
            ->filter()
            ->implode(' ; ');

        $dates = array_map(fn (string $key) => $t->{$key}?->format('d/m/Y') ?? '', array_keys(DossierTracking::STEPS));

        $current = $t->currentStepKey();
        $stage = $t->state() === 'delivered'
            ? 'Livré'
            : ($current ? DossierTracking::STEPS[$current] : 'Pas commencé');

        return array_merge(
            [
                $invoices, $dpi, $t->bl_lta ?? '', $t->dossier->number, $t->transit_order_number ?? '',
                $t->regime ?? '', $t->nature ?? '', $t->packages ?? '', $t->weight_kg !== null ? (float) $t->weight_kg : '',
            ],
            $dates,
            [$t->comments ?? '', $t->dossier->client?->name ?? '', $stage],
        );
    }
}