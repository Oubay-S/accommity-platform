<?php

namespace App\Models\Paiements;

use Illuminate\Database\Eloquent\Model;

class Versement extends Model
{
    protected $fillable = ['hote_id', 'reservation_id', 'montant', 'statut', 'date_versement'];
}
