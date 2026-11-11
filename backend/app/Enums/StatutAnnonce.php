<?php

namespace App\Enums;

enum StatutAnnonce: string
{
    case EN_ATTENTE = 'en_attente';
    case PUBLIEE = 'publiee';
    case SUSPENDUE = 'suspendue';
    case REJETEE = 'rejetee';
}
