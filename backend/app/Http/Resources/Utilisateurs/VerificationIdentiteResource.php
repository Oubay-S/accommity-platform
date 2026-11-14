<?php

namespace App\Http\Resources\Utilisateurs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ne JAMAIS exposer `chemin_document` (loi 09-08).
 */
class VerificationIdentiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_verification' => $this->id_verification,
            'type_piece' => $this->type_piece,
            'statut' => $this->statut,
            'date_soumission' => $this->date_soumission?->toIso8601String(),
            'date_traitement' => $this->date_traitement?->toIso8601String(),
            'motif_rejet' => $this->motif_rejet,
        ];
    }
}
