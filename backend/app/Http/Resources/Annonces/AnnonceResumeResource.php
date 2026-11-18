<?php

namespace App\Http\Resources\Annonces;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnonceResumeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $photos = is_array($this->photo_url) ? $this->photo_url : [];

        return [
            'id_annonce' => $this->getKey(),
            'titre' => $this->titre,
            'type_logement' => $this->type_logement,
            'capacite' => (int) $this->capacite,
            'mode_reservation' => $this->mode_reservation?->value ?? $this->mode_reservation,
            'tarif_nuit' => number_format((float) $this->tarif_nuit, 2, '.', ''),
            'ville' => $this->whenLoaded('categorie', fn () => $this->categorie?->ville),
            'region' => $this->whenLoaded('categorie', fn () => $this->categorie?->region),
            'photo_principale' => $photos[0] ?? null,
            // [PORTED] arrondi à 1 décimale, 0.0 si aucun avis
            'note_moyenne' => round((float) ($this->note_moyenne ?? 0), 1),
            'nb_avis' => (int) ($this->nb_avis ?? 0),
        ];
    }
}
