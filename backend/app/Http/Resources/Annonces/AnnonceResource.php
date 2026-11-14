<?php

namespace App\Http\Resources\Annonces;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnonceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_annonce' => $this->getKey(),
            'titre' => $this->titre,
            'description' => $this->description,
            'tarif_nuit' => $this->tarif_nuit,
            'statut' => $this->statut,
        ];
    }
}
