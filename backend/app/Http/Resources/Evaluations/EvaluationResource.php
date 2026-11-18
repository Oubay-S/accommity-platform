<?php

namespace App\Http\Resources\Evaluations;

use App\Models\Evaluations\Evaluation;
use App\Models\Utilisateurs\Profil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Evaluation
 */
class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_evaluation' => $this->getKey(),
            'note' => (float) $this->note,
            'commentaire' => $this->commentaire,
            'date_creation' => $this->date_creation->toIso8601String(),
            'auteur' => $this->whenLoaded('auteur', fn () => $this->auteur === null ? null : [
                'id_utilisateur' => $this->auteur->getKey(),
                'prenom' => $this->auteur->getAttribute('prenom'),
                'nom' => $this->auteur->getAttribute('nom'),
                'photo_url' => $this->photoAuteur(),
            ]),
            'cible' => $this->whenLoaded('cible', fn () => $this->cible === null ? null : [
                'id_utilisateur' => $this->cible->getKey(),
                'prenom' => $this->cible->getAttribute('prenom'),
                'nom' => $this->cible->getAttribute('nom'),
            ]),
            'annonce' => $this->whenLoaded('annonce', fn () => $this->annonce === null ? null : [
                'id_annonce' => $this->annonce->getKey(),
                'titre' => $this->annonce->getAttribute('titre'),
            ]),
            'notes_detaillees' => $this->whenLoaded('notesDetaillees', function (): ?array {
                $notes = $this->notesDetaillees;

                if ($notes === null) {
                    return null;
                }

                return [
                    'proprete' => (float) $notes->getAttribute('proprete'),
                    'communication' => (float) $notes->getAttribute('communication'),
                    'emplacement' => (float) $notes->getAttribute('emplacement'),
                    'rapport_qualite_prix' => (float) $notes->getAttribute('rapport_qualite_prix'),
                    'exactitude' => (float) $notes->getAttribute('exactitude'),
                ];
            }),
        ];
    }

    private function photoAuteur(): ?string
    {
        $auteur = $this->auteur;

        if ($auteur === null || ! $auteur->relationLoaded('profil')) {
            return null;
        }

        $profil = $auteur->getRelation('profil');

        if (! $profil instanceof Profil) {
            return null;
        }

        $path = $profil->getAttribute('photo_url');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
