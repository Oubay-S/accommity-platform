<?php

namespace App\Models\Annonces;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieGeographique extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'categorie_geographiques';
    protected $primaryKey = 'id_categorie';
    public $timestamps = false;

    protected $fillable = ['ville', 'region', 'pays'];
}
