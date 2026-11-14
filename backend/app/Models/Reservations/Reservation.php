<?php

namespace App\Models\Reservations;

use App\Enums\ModeReservation;
use App\Enums\StatutReservation;
use App\Models\Annonces\Annonce;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasUuids;

    protected $table = 'reservations';
    protected $primaryKey = 'id_reservation';

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    protected $fillable = [
        'id_annonce', 'id_voyageur', 'id_hote', 'id_politique',
        'date_arrivee', 'date_depart', 'nb_voyageurs', 'statut',
        'mode_reservation', 'montant_total', 'frais_service',
        'message_optionnel', 'motif_refus', 'motif_annulation',
        'date_confirmation', 'date_annulation', 'acteur_annulation',
    ];

    protected $casts = [
        'date_arrivee' => 'date',
        'date_depart' => 'date',
        'statut' => StatutReservation::class,
        'mode_reservation' => ModeReservation::class,
        'montant_total' => 'decimal:2',
        'frais_service' => 'decimal:2',
        'date_confirmation' => 'datetime',
        'date_annulation' => 'datetime',
    ];

    public function voyageur()
    {
        return $this->belongsTo(User::class, 'id_voyageur');
    }

    public function hote()
    {
        return $this->belongsTo(User::class, 'id_hote');
    }

    public function annonce()
    {
        return $this->belongsTo(Annonce::class, 'id_annonce');
    }
}
