<?php

namespace App\Enums;

enum StatutReservation: string
{
    case EN_ATTENTE = 'en_attente';
    case CONFIRMEE = 'confirmee';
    case REFUSEE = 'refusee';
    case ANNULEE = 'annulee';
    case TERMINEE = 'terminee';
    case EXPIREE = 'expiree';
}
