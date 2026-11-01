<?php

namespace App\Support;

/** Affichage des montants dans les messages : 1 000 000 FCFA. */
final class Money
{
    public static function format(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}