<?php

namespace App\Models\Paiements;

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
        'date_liberation_prevue' => 'datetime',
    ];
}
