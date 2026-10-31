<?php

namespace App\Modules\Dossiers\Actions;

use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Models\Dossier;
use App\Support\Counter;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CreateDossier
{
    /**
     * @param  array{client_id:int, services:array<int,string>, description?:?string, track?:bool}  $data
     */
    public function handle(array $data, ?CarbonInterface $openedOn = null): Dossier
    {
        $openedOn ??= now();

        return DB::transaction(function () use ($data, $openedOn) {
            $year = $openedOn->year;
            $number = sprintf('%d-%04d', $year, Counter::next("dossier-{$year}"));

            $dossier = Dossier::create([
                'number' => $number,
                'client_id' => $data['client_id'],
                'opened_on' => $openedOn->toDateString(),
                'status' => DossierStatus::Nouvelle,
                'services' => array_values($data['services']),
                'description' => $data['description'] ?? null,
            ]);

            // Le suivi du transit est actif par défaut.
            if ($data['track'] ?? true) {
                $dossier->tracking()->create();
            }

            return $dossier;
        });
    }
}