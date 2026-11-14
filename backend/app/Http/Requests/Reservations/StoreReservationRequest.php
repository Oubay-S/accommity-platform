<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'annonce_id' => ['required', 'exists:annonces,id_annonce'],
            'date_arrivee' => ['required', 'date', 'after:today'],
            'date_depart' => ['required', 'date', 'after:date_arrivee'],
            'nombre_voyageurs' => ['required', 'integer', 'min:1'],
        ];
    }
}
