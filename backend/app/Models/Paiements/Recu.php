<?php

namespace App\Models\Paiements;

use Illuminate\Database\Eloquent\Model;

class Recu extends Model
{
    protected $fillable = ['paiement_id', 'numero_recu', 'pdf_url'];
}
