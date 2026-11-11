<?php

namespace App\Models\Annonces;

use Illuminate\Database\Eloquent\Model;

class PolitiqueAnnulation extends Model
{
    protected $fillable = ['nom', 'description', 'delai_heures', 'pourcentage_remboursement'];
}
