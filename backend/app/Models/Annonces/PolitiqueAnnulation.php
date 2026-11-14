<?php

namespace App\Models\Annonces;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PolitiqueAnnulation extends Model
{
    use HasUuids;

    protected $table = 'politique_annulations';
    protected $primaryKey = 'id_politique';
    public $timestamps = false;

    protected $fillable = [
        'type_politique', 'delai_remb_total', 'delai_remb_partiel',
        'taux_remb_partiel', 'description',
    ];
}
