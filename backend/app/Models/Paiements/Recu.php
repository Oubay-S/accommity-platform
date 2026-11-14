<?php

namespace App\Models\Paiements;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Recu extends Model
{
    use HasUuids;

    protected $table = 'recus';
    protected $primaryKey = 'id_recu';

    const CREATED_AT = 'date_generation';
    const UPDATED_AT = null;

    protected $fillable = ['id_paiement', 'numero_facture', 'chemin_pdf'];
}
