<?php

namespace App\Models\Annonces;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Calendrier extends Model
{
    use HasUuids;

    protected $table = 'calendriers';
    protected $primaryKey = 'id_calendrier';
    public $timestamps = false;

    protected $fillable = ['id_annonce', 'date_debut', 'date_fin', 'est_disponible', 'type_blockage'];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'est_disponible' => 'boolean',
    ];
}
