<?php

namespace App\Models\Annonces;

use Illuminate\Database\Eloquent\Model;

class Calendrier extends Model
{
    protected $fillable = ['annonce_id', 'date', 'est_disponible', 'prix_specifique'];

    protected $casts = [
        'date' => 'date',
        'est_disponible' => 'boolean'
    ];
}
