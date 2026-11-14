<?php

namespace App\Models\Paiements;

use App\Models\Reservations\Reservation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasUuids;

    protected $table = 'paiements';
    protected $primaryKey = 'id_paiement';

    const CREATED_AT = 'date_transaction';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_reservation', 'id_voyageur', 'montant', 'methode_paiement', 'statut',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }
}
