<?php

namespace App\Http\Requests\Annonces;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnonceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->estHoteVerifie() ?? false; }
    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'prix_par_nuit' => ['required', 'numeric', 'min:50'],
            'type_logement' => ['required', 'string'],
            'adresse' => ['required', 'string'],
            'ville' => ['required', 'string'],
            'capacite_voyageurs' => ['required', 'integer', 'min:1'],
            'mode_reservation' => ['required', 'in:instantanee,sur_demande'],
        ];
    }
}
