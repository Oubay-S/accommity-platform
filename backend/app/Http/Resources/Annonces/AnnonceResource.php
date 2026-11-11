<?php

namespace App\Http\Resources\Annonces;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnonceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'prix_par_nuit' => $this->prix_par_nuit,
            'statut' => $this->statut,
        ];
    }
}
