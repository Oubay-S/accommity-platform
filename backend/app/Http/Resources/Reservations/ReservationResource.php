<?php

namespace App\Http\Resources\Reservations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut,
            'prix_total' => $this->prix_total,
        ];
    }
}
