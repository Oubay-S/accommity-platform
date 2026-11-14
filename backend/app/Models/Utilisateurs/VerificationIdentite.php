<?php

namespace App\Models\Utilisateurs;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VerificationIdentite extends Model
{
    use HasUuids;

    protected $table = 'verification_identites';
    protected $primaryKey = 'id_verification';

    const CREATED_AT = 'date_soumission';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_utilisateur', 'type_piece', 'statut', 'motif_rejet',
        'chemin_document', 'id_admin_traitant', 'date_traitement',
    ];

    public function estValidee(): bool
    {
        return $this->statut === 'validee';
    }
}
