<?php

namespace App\Models\Paiements;

use Illuminate\Database\Eloquent\Model;
use App\Models\Reservations\Reservation;

class Paiement extends Model
{
    protected $fillable = [
        'reservation_id', 'reference_transaction', 'montant', 'mode_paiement',
        'statut', 'date_paiement'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
