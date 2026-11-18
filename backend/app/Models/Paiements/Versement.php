<?php

namespace App\Models\Paiements;

use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    public function hote()
    {
        return $this->belongsTo(User::class, 'id_hote');
    }
}
