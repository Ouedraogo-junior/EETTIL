<?php

namespace App\Modules\Dossiers\Enums;

enum DossierStatus: string
{
    case Nouvelle = 'nouvelle';
    case EnCours = 'en_cours';
    case Terminee = 'terminee';
    case Cloturee = 'cloturee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Nouvelle => 'Nouvelle',
            self::EnCours => 'En cours',
            self::Terminee => 'Terminée',
            self::Cloturee => 'Clôturée',
            self::Annulee => 'Annulée',
        };
    }
}