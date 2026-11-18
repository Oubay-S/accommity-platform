<?php

namespace App\Http\Resources\Annonces;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnonceDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $photos = is_array($this->photo_url) ? $this->photo_url : [];

        return array_merge((new AnnonceResumeResource($this->resource))->toArray($request), [
            'description' => $this->description,
            'adresse' => $this->adresse,
            'reglement_interieur' => $this->reglement_interieur,
            'photos' => $photos,
            'equipements' => array_values(array_filter(
                array_map('trim', explode(',', (string) ($this->equipements ?? ''))),
                fn (string $v) => $v !== '',
            )),
            'politique_annulation' => $this->whenLoaded('politiqueAnnulation', function () {
                $p = $this->politiqueAnnulation;
                if (!$p) {
                    return null;
                }

                return [
                    'type_politique' => $p->type_politique,
                    'description' => $p->description,
                    'delai_remb_total' => (int) $p->delai_remb_total,
                    'delai_remb_partiel' => (int) $p->delai_remb_partiel,
                    'taux_remb_partiel' => (float) $p->taux_remb_partiel,
                ];
            }),
            'hote' => $this->whenLoaded('hote', function () {
                $h = $this->hote;
                if (!$h) {
                    return null;
                }

                return [
                    'id_utilisateur' => $h->getKey(),
                    'prenom' => $h->prenom,
                    'nom' => $h->nom,
                    'photo_url' => $h->relationLoaded('profil') ? $h->profil?->photo_url : null,
                    'date_creation' => $h->date_creation?->toIso8601String(),
                ];
            }),
            'date_creation' => $this->date_creation?->toIso8601String(),
        ]);
    }
}
