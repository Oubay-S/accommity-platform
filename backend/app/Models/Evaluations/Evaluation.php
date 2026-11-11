<?php

namespace App\Models\Evaluations;

use Illuminate\Database\Eloquent\Model;
use App\Models\Utilisateurs\User;
use App\Models\Annonces\Annonce;

class Evaluation extends Model
{
    protected $fillable = [
        'auteur_id', 'cible_id', 'annonce_id', 'note_globale', 'commentaire',
        'est_signale', 'est_modere'
    ];

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}
