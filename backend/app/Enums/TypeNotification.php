<?php

namespace App\Enums;

/**
 * Types de notification in-app.
 *
 * Les valeurs DOIVENT rester alignées sur la contrainte CHECK de la colonne
 * `notifications.type_alerte` (schéma PostgreSQL v1).
 */
enum TypeNotification: string
{
    case RESERVATION = 'reservation';
    case PAIEMENT = 'paiement';
    case AVIS = 'avis';
    case RAPPEL = 'rappel';
    case SYSTEME = 'systeme';

    public function libelle(): string
    {
        return match ($this) {
            self::RESERVATION => 'Réservation',
            self::PAIEMENT => 'Paiement',
            self::AVIS => 'Avis',
            self::RAPPEL => 'Rappel',
            self::SYSTEME => 'Système',
        };
    }
}
