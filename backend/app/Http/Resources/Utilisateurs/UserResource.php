<?php

namespace App\Http\Resources\Utilisateurs;

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

            'profil' => $this->whenLoaded('profil', fn () => $this->profil === null
                ? null
                : (new ProfilResource($this->profil))->resolve()),

            'verification_identite' => $this->whenLoaded('verificationIdentite', fn () => $this->verificationIdentite === null
                ? null
                : (new VerificationIdentiteResource($this->verificationIdentite))->resolve()),
        ];
    }
}
