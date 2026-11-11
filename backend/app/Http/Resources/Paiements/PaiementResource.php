<?php

namespace App\Http\Resources\Paiements;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaiementResource extends JsonResource
{
    public function toArray(Request $request): array { return parent::toArray($request); }
}
