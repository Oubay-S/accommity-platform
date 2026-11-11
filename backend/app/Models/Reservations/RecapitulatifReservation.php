<?php

namespace App\Models\Reservations;

use Illuminate\Database\Eloquent\Model;

class RecapitulatifReservation extends Model
{
    protected $fillable = ['reservation_id', 'sous_total', 'frais_service', 'total', 'details_calcul'];
}
