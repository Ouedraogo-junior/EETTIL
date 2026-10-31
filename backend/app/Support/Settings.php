<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Paramètres de la société : valeurs par défaut du fichier de configuration, surchargées par la base. */
final class Settings
{
    /** @return array<string,mixed> */
    public static function all(): array
    {
        $stored = DB::table('settings')->pluck('value', 'name')->map(fn ($v) => json_decode($v, true))->all();

        return array_merge(config('eettil.settings'), $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(['name' => $key], ['value' => json_encode($value)]);
    }
}