<?php

namespace App\Models\Evaluations;

use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasUuids;

    protected $table = 'evaluations';
    protected $primaryKey = 'id_evaluation';

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';

    protected $fillable = [
        'id_reservation', 'id_auteur', 'id_cible', 'id_annonce',
        'type_auteur', 'note', 'commentaire', 'est_signale',
        'motif_signalement',
    ];

    protected $casts = [
        'note' => 'decimal:2',
        'est_signale' => 'boolean',
    ];

    public function auteur()
    {
        return $this->belongsTo(User::class, 'id_auteur');
    }
}
