<?php

namespace App\Models\Paiements;

use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
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
        'date_transaction' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    public function voyageur()
    {
        return $this->belongsTo(User::class, 'id_voyageur');
    }
}
