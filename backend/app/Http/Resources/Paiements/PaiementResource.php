<?php

namespace App\Http\Resources\Paiements;

use App\Http\Resources\Reservations\ReservationResource;
use App\Models\Paiements\Paiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Paiement
 */
class PaiementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_paiement' => $this->getKey(),
            'id_reservation' => $this->id_reservation,
            'id_voyageur' => $this->id_voyageur,
            'montant' => (float) $this->montant,
            'methode_paiement' => $this->methode_paiement,
            'statut' => $this->statut,
            'date_transaction' => Carbon::parse($this->date_transaction)->toIso8601String(),
            'recu' => $this->whenLoaded('recu', fn () => $this->recu ? [
                'id_recu' => $this->recu->id_recu,
                'id_paiement' => $this->recu->id_paiement,
                'numero_facture' => $this->recu->numero_facture,
                'chemin_pdf' => $this->recu->chemin_pdf,
                'date_generation' => Carbon::parse($this->recu->date_generation)->toIso8601String(),
            ] : null),
            'reservation' => $this->whenLoaded('reservation', fn () => new ReservationResource($this->reservation)),
        ];
    }
}
