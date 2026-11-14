<?php

namespace App\Http\Resources\Utilisateurs;

use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrat public d'un utilisateur pour l'API Angular.
 *
 * Ne jamais exposer `mot_de_passe` ni `verification_identites.chemin_document`
 * (loi 09-08). Les dates sont au format ISO 8601.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $estAdmin = $this->estAdmin();

        return [
            'id_utilisateur' => $this->getKey(),
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'role' => $estAdmin ? 'admin' : ($this->est_hote ? 'hote' : 'voyageur'),
            'est_hote' => (bool) $this->est_hote,
            'est_voyageur' => (bool) $this->est_voyageur,
            'est_admin' => $estAdmin,
            'est_actif' => (bool) $this->est_actif,
            'est_hote_verifie' => $this->estHoteVerifie(),
            'date_creation' => $this->date_creation?->toIso8601String(),

            'profil' => $this->whenLoaded('profil', fn () => $this->profil === null ? null : [
                'id_profil' => $this->profil->id_profil,
                'photo_url' => $this->profil->photo_url,
                'description' => $this->profil->description,
                'note_moyenne' => (float) $this->profil->note_moyenne,
            ]),

            'verification_identite' => $this->whenLoaded('verificationIdentite', function () {
                /** @var VerificationIdentite|null $verification */
                $verification = $this->verificationIdentite;

                return $verification === null ? null : [
                    'id_verification' => $verification->id_verification,
                    'type_piece' => $verification->type_piece,
                    'statut' => $verification->statut,
                    'date_soumission' => $verification->date_soumission?->toIso8601String(),
                    'date_traitement' => $verification->date_traitement?->toIso8601String(),
                    'motif_rejet' => $verification->motif_rejet,
                ];
            }),
        ];
    }
}
