<?php

namespace App\Models\Annonces;

use Illuminate\Database\Eloquent\Model;
use App\Models\Utilisateurs\User;
use App\Enums\StatutAnnonce;
use App\Enums\ModeReservation;

class Annonce extends Model
{
    protected $fillable = [
        'hote_id', 'categorie_id', 'politique_id', 'titre', 'description',
        'type_logement', 'adresse', 'ville', 'code_postal', 'prix_par_nuit',
        'capacite_voyageurs', 'nombre_chambres', 'nombre_lits', 'nombre_salles_bain',
        'mode_reservation', 'statut'
    ];

    protected $casts = [
        'statut' => StatutAnnonce::class,
        'mode_reservation' => ModeReservation::class,
        'prix_par_nuit' => 'decimal:2'
    ];

    public function hote()
    {
        return $this->belongsTo(User::class, 'hote_id');
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieGeographique::class, 'categorie_id');
    }

    public function politiqueAnnulation()
    {
        return $this->belongsTo(PolitiqueAnnulation::class, 'politique_id');
    }

    public function calendriers()
    {
        return $this->hasMany(Calendrier::class);
    }
}
