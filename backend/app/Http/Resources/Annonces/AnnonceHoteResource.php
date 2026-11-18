<?php

namespace App\Http\Resources\Annonces;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation complète d'une annonce pour son hôte (édition, liste).
 */
class AnnonceHoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $photos = is_array($this->photo_url) ? $this->photo_url : [];

        return [
            'id_annonce' => $this->getKey(),
            'id_hote' => $this->id_hote,
            'titre' => $this->titre,
            'description' => $this->description,
            'type_logement' => $this->type_logement,
            'adresse' => $this->adresse,
            'capacite' => (int) $this->capacite,
            'tarif_nuit' => number_format((float) $this->tarif_nuit, 2, '.', ''),
            'mode_reservation' => $this->mode_reservation?->value ?? $this->mode_reservation,
            'statut' => $this->statut?->value ?? $this->statut,
            'motif_moderation' => $this->motif_moderation,
            'equipements' => $this->equipementsEnTableau(),
            'reglement_interieur' => $this->reglement_interieur,
            'photos' => array_values(array_map(fn (string $photo) => $this->urlPhoto($photo), $photos)),
            'photo_principale' => isset($photos[0]) ? $this->urlPhoto($photos[0]) : null,
            'categorie' => $this->whenLoaded('categorie', fn () => $this->categorie ? [
                'id_categorie' => $this->categorie->getKey(),
                'ville' => $this->categorie->ville,
                'region' => $this->categorie->region,
                'pays' => $this->categorie->pays,
            ] : null),
            'politique_annulation' => $this->whenLoaded('politiqueAnnulation', fn () => $this->politiqueAnnulation ? [
                'id_politique' => $this->politiqueAnnulation->getKey(),
                'type_politique' => $this->politiqueAnnulation->type_politique,
                'description' => $this->politiqueAnnulation->description,
                'delai_remb_total' => (int) $this->politiqueAnnulation->delai_remb_total,
                'delai_remb_partiel' => (int) $this->politiqueAnnulation->delai_remb_partiel,
                'taux_remb_partiel' => (float) $this->politiqueAnnulation->taux_remb_partiel,
            ] : null),
            'note_moyenne' => round((float) ($this->note_moyenne ?? 0), 1),
            'nb_avis' => (int) ($this->nb_avis ?? 0),
            'a_des_reservations_actives' => (int) ($this->reservations_actives_count ?? 0) > 0,
            'date_creation' => $this->date_creation?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function equipementsEnTableau(): array
    {
        if (is_array($this->equipements)) {
            return array_values($this->equipements);
        }

        return array_values(array_filter(
            array_map('trim', explode(',', (string) ($this->equipements ?? ''))),
            fn (string $valeur) => $valeur !== '',
        ));
    }

    private function urlPhoto(string $photo): string
    {
        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return $photo;
        }

        return url($photo);
    }
}
