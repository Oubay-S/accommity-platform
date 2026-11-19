<?php

namespace App\Models\Reservations;

use App\Enums\ModeReservation;
use App\Enums\StatutReservation;
use App\Models\Annonces\Annonce;
use App\Models\Annonces\PolitiqueAnnulation;
use App\Models\Paiements\Paiement;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reservations';

    protected $primaryKey = 'id_reservation';

    const CREATED_AT = 'date_creation';

    const UPDATED_AT = 'date_modification';

    protected $fillable = [
        'id_reservation', 'id_annonce', 'id_voyageur', 'id_hote', 'id_politique',
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

    /** @return BelongsTo<User, $this> */
    public function voyageur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_voyageur');
    }

    /** @return BelongsTo<User, $this> */
    public function hote(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_hote');
    }

    /** @return BelongsTo<Annonce, $this> */
    public function annonce(): BelongsTo
    {
        return $this->belongsTo(Annonce::class, 'id_annonce');
    }

    /** @return BelongsTo<PolitiqueAnnulation, $this> */
    public function politique(): BelongsTo
    {
        return $this->belongsTo(PolitiqueAnnulation::class, 'id_politique');
    }

    /** @return HasOne<Paiement, $this> */
    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class, 'id_reservation');
    }

    /** @return HasOne<Minuterie, $this> */
    public function minuterie(): HasOne
    {
        return $this->hasOne(Minuterie::class, 'id_reservation');
    }

    /** @return HasOne<RecapitulatifReservation, $this> */
    public function recapitulatif(): HasOne
    {
        return $this->hasOne(RecapitulatifReservation::class, 'id_reservation');
    }
}
