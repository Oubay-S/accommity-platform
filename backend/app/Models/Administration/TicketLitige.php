<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;

class TicketLitige extends Model
{
    protected $fillable = [
        'plaignant_id', 'defendeur_id', 'reservation_id', 'objet', 'description',
        'statut', 'resolution', 'traite_par'
    ];
}
