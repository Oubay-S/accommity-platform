<?php

namespace App\Models\Paiements;

use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Reservation|null $reservation
 * @property-read User|null $voyageur
 * @property-read Paiement|null $paiement
 */
class Remboursement extends Model
{
    use HasUuids;

    protected $table = 'remboursements';

    protected $primaryKey = 'id_remboursement';

    const CREATED_AT = 'date_remboursement';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_remboursement', 'id_reservation', 'id_voyageur', 'id_paiement', 'montant',
        'taux_applique', 'motif',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'taux_applique' => 'decimal:2',
        'date_remboursement' => 'datetime',
    ];

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    /** @return BelongsTo<User, $this> */
    public function voyageur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_voyageur');
    }

    /** @return BelongsTo<Paiement, $this> */
    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class, 'id_paiement');
    }
}
