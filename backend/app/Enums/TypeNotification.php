<?php

namespace App\Enums;

enum TypeNotification: string
{
    case RESERVATION_DEMANDE = 'reservation_demande';
    case RESERVATION_CONFIRMEE = 'reservation_confirmee';
    case RESERVATION_ANNULEE = 'reservation_annulee';
    case PAIEMENT_RECU = 'paiement_recu';
    case VERSEMENT_EFFECTUE = 'versement_effectue';
    case NOUVEL_AVIS = 'nouvel_avis';
    case IDENTITE_VALIDEE = 'identite_validee';
    case IDENTITE_REJETEE = 'identite_rejetee';
}
