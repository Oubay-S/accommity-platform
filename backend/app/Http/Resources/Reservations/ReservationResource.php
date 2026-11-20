<?php

namespace App\Http\Resources\Reservations;

use App\Models\Annonces\Annonce;
use App\Models\Reservations\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reservation
 */
class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statut = $this->statut->value;
        $mode = $this->mode_reservation->value;

        $annoncePhoto = null;
        if ($this->relationLoaded('annonce') && $this->annonce) {
            /** @var Annonce $annonce */
            $annonce = $this->annonce;
            $photos = (array) $annonce->photo_url;
            $firstPhoto = $photos[0] ?? null;
            $annoncePhoto = $firstPhoto ? (str_starts_with($firstPhoto, 'http') ? $firstPhoto : url($firstPhoto)) : null;
        }

        return [
            'id_reservation' => $this->getKey(),
            'id_annonce' => $this->id_annonce,
            'id_voyageur' => $this->id_voyageur,
            'id_hote' => $this->id_hote,
            'id_politique' => $this->id_politique,
            'date_arrivee' => Carbon::parse($this->date_arrivee)->format('Y-m-d'),
            'date_depart' => Carbon::parse($this->date_depart)->format('Y-m-d'),
            'nb_voyageurs' => (int) $this->nb_voyageurs,
            'statut' => $statut,
            'mode_reservation' => $mode,
            'montant_total' => (float) $this->montant_total,
            'frais_service' => (float) $this->frais_service,
            'message_optionnel' => $this->message_optionnel,
            'motif_refus' => $this->motif_refus,
            'motif_annulation' => $this->motif_annulation,
            'acteur_annulation' => $this->acteur_annulation,
            'date_confirmation' => $this->date_confirmation ? Carbon::parse($this->date_confirmation)->toIso8601String() : null,
            'date_annulation' => $this->date_annulation ? Carbon::parse($this->date_annulation)->toIso8601String() : null,
            'date_creation' => Carbon::parse($this->date_creation)->toIso8601String(),

            'annonce' => $this->whenLoaded('annonce', fn () => [
                'id_annonce' => $this->annonce->id_annonce,
                'titre' => $this->annonce->titre,
                'type_logement' => $this->annonce->type_logement,
                'adresse' => $this->annonce->adresse,
                'ville' => $this->annonce->categorie?->ville,
                'tarif_nuit' => (float) $this->annonce->tarif_nuit,
                'photo_principale' => $annoncePhoto,
            ]),

            'voyageur' => $this->whenLoaded('voyageur', fn () => [
                'id_utilisateur' => $this->voyageur->id_utilisateur,
                'nom' => $this->voyageur->nom,
                'prenom' => $this->voyageur->prenom,
                'email' => $this->voyageur->email,
                'telephone' => $this->voyageur->telephone,
                'photo_url' => $this->voyageur->profil?->photo_url,
            ]),

            'hote' => $this->whenLoaded('hote', fn () => [
                'id_utilisateur' => $this->hote->id_utilisateur,
                'nom' => $this->hote->nom,
                'prenom' => $this->hote->prenom,
                'email' => $this->hote->email,
                'photo_url' => $this->hote->profil?->photo_url,
            ]),

            'politique' => $this->whenLoaded('politique', fn () => [
                'type_politique' => $this->politique->type_politique,
                'description' => $this->politique->description,
                'delai_remb_total' => (int) $this->politique->delai_remb_total,
                'delai_remb_partiel' => (int) $this->politique->delai_remb_partiel,
                'taux_remb_partiel' => (float) $this->politique->taux_remb_partiel,
            ]),

            'paiement' => $this->whenLoaded('paiement', fn () => $this->paiement ? [
                'id_paiement' => $this->paiement->id_paiement,
                'montant' => (float) $this->paiement->montant,
                'methode_paiement' => $this->paiement->methode_paiement,
                'statut' => $this->paiement->statut,
                'date_transaction' => Carbon::parse($this->paiement->date_transaction)->toIso8601String(),
            ] : null),

            'minuterie' => $this->whenLoaded('minuterie', fn () => $this->minuterie ? [
                'id_minuterie' => $this->minuterie->id_minuterie,
                'type_minuterie' => $this->minuterie->type_minuterie,
                'date_echeance' => Carbon::parse($this->minuterie->date_echeance)->toIso8601String(),
                'est_active' => (bool) $this->minuterie->est_active,
            ] : null),
        ];
    }
}
