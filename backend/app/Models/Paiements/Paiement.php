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
 */
class Paiement extends Model
{
    use HasUuids;

    protected $table = 'paiements';

    protected $primaryKey = 'id_paiement';

    const CREATED_AT = 'date_transaction';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_paiement', 'id_reservation', 'id_voyageur', 'montant', 'methode_paiement', 'statut',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_transaction' => 'datetime',
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

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<Recu, $this> */
    public function recu(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Recu::class, 'id_paiement');
    }
}
