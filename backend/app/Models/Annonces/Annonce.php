<?php

namespace App\Models\Annonces;

use App\Enums\ModeReservation;
use App\Enums\StatutAnnonce;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Annonce extends Model
{
    use HasUuids;

    protected $table = 'annonces';
    protected $primaryKey = 'id_annonce';

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_hote', 'id_categorie', 'id_politique', 'titre', 'description',
        'photo_url', 'type_logement', 'adresse', 'capacite', 'tarif_nuit',
        'mode_reservation', 'statut', 'motif_moderation', 'equipements',
        'reglement_interieur',
    ];

    protected $casts = [
        'statut' => StatutAnnonce::class,
        'mode_reservation' => ModeReservation::class,
        'tarif_nuit' => 'decimal:2',
    ];

    public function hote()
    {
        return $this->belongsTo(User::class, 'id_hote');
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieGeographique::class, 'id_categorie');
    }

    public function politiqueAnnulation()
    {
        return $this->belongsTo(PolitiqueAnnulation::class, 'id_politique');
    }

    public function calendriers()
    {
        return $this->hasMany(Calendrier::class, 'id_annonce');
    }
}
