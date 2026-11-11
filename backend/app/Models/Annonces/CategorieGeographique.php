<?php

namespace App\Models\Annonces;

use Illuminate\Database\Eloquent\Model;

class CategorieGeographique extends Model
{
    protected $fillable = ['nom', 'description', 'icone'];
}
