<?php

namespace App\Http\Resources\Utilisateurs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_utilisateur' => $this->getKey(),
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'est_hote' => $this->est_hote,
            'est_voyageur' => $this->est_voyageur,
            'est_admin' => $this->estAdmin(),
            'est_hote_verifie' => $this->estHoteVerifie(),
        ];
    }
}
