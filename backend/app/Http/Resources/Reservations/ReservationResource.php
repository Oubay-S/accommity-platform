<?php

namespace App\Http\Resources\Reservations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_reservation' => $this->getKey(),
            'statut' => $this->statut,
            'montant_total' => $this->montant_total,
        ];
    }
}
