<?php

namespace App\Models\Paiements;

use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Reservation|null $reservation
 * @property-read User|null $hote
 */
class Versement extends Model
{
    use HasUuids;

    protected $table = 'versements';

    protected $primaryKey = 'id_versement';

    public $timestamps = false;

    protected $fillable = [
        'id_reservation', 'id_hote', 'montant', 'date_versement',
        'reference_bancaire', 'date_liberation_prevue', 'statut',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_versement' => 'datetime',
        'date_liberation_prevue' => 'datetime',
    ];

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    /** @return BelongsTo<User, $this> */
    public function hote(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_hote');
    }
}
