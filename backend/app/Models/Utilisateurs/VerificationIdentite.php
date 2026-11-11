<?php

namespace App\Models\Utilisateurs;

use Illuminate\Database\Eloquent\Model;

class VerificationIdentite extends Model
{
    protected $fillable = ['user_id', 'type_document', 'numero_document', 'document_url', 'statut', 'motif_rejet', 'verifie_a'];

    public function estValidee(): bool
    {
        return $this->statut === 'validee';
    }
}
