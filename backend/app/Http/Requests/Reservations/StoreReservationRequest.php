<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Tolérance sur les conventions d'attributs
        if ($this->has('annonce_id') && ! $this->has('id_annonce')) {
            $this->merge(['id_annonce' => $this->input('annonce_id')]);
        }
        if ($this->has('nombre_voyageurs') && ! $this->has('nb_voyageurs')) {
            $this->merge(['nb_voyageurs' => $this->input('nombre_voyageurs')]);
        }
    }

    public function rules(): array
    {
        return [
            'id_annonce' => ['required', 'string', 'exists:annonces,id_annonce'],
            'date_arrivee' => ['required', 'date', 'after_or_equal:today'],
            'date_depart' => ['required', 'date', 'after:date_arrivee'],
            'nb_voyageurs' => ['required', 'integer', 'min:1'],
            'message_optionnel' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
