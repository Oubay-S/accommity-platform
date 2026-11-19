<?php

namespace App\Models\Annonces;

use App\Enums\ModeReservation;
use App\Enums\StatutAnnonce;
use App\Models\Evaluations\Evaluation;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Annonce extends Model
{
    use HasFactory, HasUuids;

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
        'photo_url' => 'array',
    ];

    public function hote(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_hote');
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieGeographique::class, 'id_categorie');
    }

    public function politiqueAnnulation(): BelongsTo
    {
        return $this->belongsTo(PolitiqueAnnulation::class, 'id_politique');
    }

    public function politique(): BelongsTo
    {
        return $this->politiqueAnnulation();
    }

    public function calendriers(): HasMany
    {
        return $this->hasMany(Calendrier::class, 'id_annonce');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'id_annonce');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'id_annonce');
    }
}
