<?php

namespace App\Models\Reservations;

use Illuminate\Database\Eloquent\Model;
use App\Models\Utilisateurs\User;
use App\Models\Annonces\Annonce;
use App\Enums\StatutReservation;

class Reservation extends Model
{
    protected $fillable = [
        'voyageur_id', 'annonce_id', 'date_arrivee', 'date_depart',
        'nombre_voyageurs', 'prix_total', 'frais_service', 'statut', 'motif_refus'
    ];

    protected $casts = [
        'date_arrivee' => 'date',
        'date_depart' => 'date',
        'statut' => StatutReservation::class,
        'prix_total' => 'decimal:2',
        'frais_service' => 'decimal:2'
    ];

    public function voyageur()
    {
        return $this->belongsTo(User::class, 'voyageur_id');
    }

    public function annonce()
    {
        return $this->belongsTo(Annonce::class, 'annonce_id');
    }
}
