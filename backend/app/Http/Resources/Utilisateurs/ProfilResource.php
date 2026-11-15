<?php

namespace App\Http\Resources\Utilisateurs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProfilResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_profil' => $this->id_profil,
            // Le chemin relatif est stocké en base ; l'URL publique est calculée.
            'photo_url' => $this->photo_url
                ? Storage::disk('public')->url($this->photo_url)
                : null,
            'description' => $this->description,
            'note_moyenne' => (float) $this->note_moyenne,
        ];
    }
}
