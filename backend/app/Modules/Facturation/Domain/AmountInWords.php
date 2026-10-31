<?php

namespace App\Modules\Facturation\Domain;

use InvalidArgumentException;

/**
 * Montant en lettres, en français, en majuscules et sans trait d'union
 * (« QUINZE MILLIONS SOIXANTE NEUF MILLE SIX CENT SOIXANTE NEUF »), comme sur le modèle du client.
 */
final class AmountInWords
{
    private const UNITS = [
        'zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
    ];

    private const TENS = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante'];

    /** « QUINZE MILLIONS … » */
    public static function words(int $n): string
    {
        if ($n < 0) {
            throw new InvalidArgumentException('Le montant en lettres ne s’applique pas à un montant négatif.');
        }
        if ($n === 0) {
            return 'ZÉRO';
        }

        $billions = intdiv($n, 1_000_000_000);
        $millions = intdiv($n % 1_000_000_000, 1_000_000);
        $thousands = intdiv($n % 1_000_000, 1000);
        $rest = $n % 1000;

        // Milliard et million sont des noms : « deux cents millions », « quatre vingts millions ».
        // Mille est invariable : « deux cent mille », « quatre vingt mille ».
        $parts = [];
        if ($billions) {
            $parts[] = self::hundreds($billions, true).($billions > 1 ? ' milliards' : ' milliard');
        }
        if ($millions) {
            $parts[] = self::hundreds($millions, true).($millions > 1 ? ' millions' : ' million');
        }
        if ($thousands) {
            $parts[] = $thousands === 1 ? 'mille' : self::hundreds($thousands, false).' mille';
        }
        if ($rest) {
            $parts[] = self::hundreds($rest, true);
        }

        return mb_strtoupper(implode(' ', $parts));
    }

    /** « QUINZE MILLIONS … FRANCS CFA » (« UN FRANC CFA » au singulier). */
    public static function forInvoice(int $n): string
    {
        return self::words($n).($n > 1 ? ' FRANCS CFA' : ' FRANC CFA');
    }

    /** De 1 à 999. $final : le nombre est-il suivi de rien (ou d'un nom) ? Alors « cents » et « quatre vingts » prennent un s. */
    private static function hundreds(int $x, bool $final): string
    {
        $h = intdiv($x, 100);
        $r = $x % 100;
        $out = [];

        if ($h === 1) {
            $out[] = 'cent';
        } elseif ($h > 1) {
            $out[] = self::UNITS[$h].' cent'.($r === 0 && $final ? 's' : '');
        }
        if ($r) {
            $out[] = self::tens($r, $final);
        }

        return implode(' ', $out);
    }

    /** De 1 à 99. */
    private static function tens(int $x, bool $final): string
    {
        if ($x < 17) {
            return self::UNITS[$x];
        }
        if ($x < 20) {
            return 'dix '.self::UNITS[$x - 10];
        }
        if ($x < 70) {
            $t = intdiv($x, 10);
            $u = $x % 10;

            return self::TENS[$t].($u === 1 ? ' et un' : ($u ? ' '.self::UNITS[$u] : ''));
        }
        if ($x < 80) {
            $u = $x - 60;

            return 'soixante'.($u === 11 ? ' et onze' : ' '.self::tens($u, $final));
        }

        $u = $x - 80;

        return $u === 0
            ? ($final ? 'quatre vingts' : 'quatre vingt')
            : 'quatre vingt '.self::tens($u, $final);
    }
}