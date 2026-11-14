<?php

namespace App\Http\Resources\Utilisateurs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Ne JAMAIS exposer `chemin_document` (loi 09-08).
 *
 * Le bloc `utilisateur` n'apparaît que lorsque la relation est chargée
 * (listes et actions du back-office admin).
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
            'document_disponible' => $this->chemin_document !== null
                && Storage::disk('local')->exists($this->chemin_document),

            'utilisateur' => $this->whenLoaded('utilisateur', fn () => $this->utilisateur === null ? null : [
                'id_utilisateur' => $this->utilisateur->id_utilisateur,
                'nom' => $this->utilisateur->nom,
                'prenom' => $this->utilisateur->prenom,
                'email' => $this->utilisateur->email,
                'telephone' => $this->utilisateur->telephone,
                'est_hote' => (bool) $this->utilisateur->est_hote,
            ]),
        ];
    }
}
