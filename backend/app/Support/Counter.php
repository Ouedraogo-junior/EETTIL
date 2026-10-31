<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Counter
{
    /** Retourne le numéro suivant du compteur $name (ex. « dossier-2026 »). Jamais réutilisé. */
    public static function next(string $name): int
    {
        return DB::transaction(function () use ($name) {
            DB::table('counters')->insertOrIgnore(['name' => $name, 'value' => 0]);

            $value = (int) DB::table('counters')->where('name', $name)->lockForUpdate()->value('value') + 1;
            DB::table('counters')->where('name', $name)->update(['value' => $value]);

            return $value;
        });
    }
}