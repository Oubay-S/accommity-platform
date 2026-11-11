<?php

namespace App\Models\Paiements;

use Illuminate\Database\Eloquent\Model;

class Remboursement extends Model
{
    protected $fillable = ['paiement_id', 'montant', 'motif', 'statut', 'date_remboursement'];
}
