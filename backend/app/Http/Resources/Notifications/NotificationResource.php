<?php

namespace App\Http\Resources\Notifications;

use App\Enums\TypeNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_notification' => $this->id_notification,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'type_alerte' => $this->type_alerte instanceof TypeNotification
                ? $this->type_alerte->value
                : $this->type_alerte,
            'est_lue' => (bool) $this->est_lue,
            'date_creation' => $this->date_creation?->toIso8601String(),
            'id_reservation' => $this->id_reservation,
            'data' => $this->data,
        ];
    }
}
