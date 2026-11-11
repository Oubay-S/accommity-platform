<?php

namespace App\Http\Resources\Evaluations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array { return parent::toArray($request); }
}
