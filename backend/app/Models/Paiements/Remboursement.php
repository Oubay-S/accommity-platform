<?php

namespace App\Models\Paiements;

use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Remboursement extends Model
{
    use HasUuids;

    protected $table = 'remboursements';
    protected $primaryKey = 'id_remboursement';

    const CREATED_AT = 'date_remboursement';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_reservation', 'id_voyageur', 'id_paiement', 'montant',
        'taux_applique', 'motif',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'taux_applique' => 'decimal:2',
        'date_remboursement' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    public function voyageur()
    {
        return $this->belongsTo(User::class, 'id_voyageur');
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class, 'id_paiement');
    }
}
