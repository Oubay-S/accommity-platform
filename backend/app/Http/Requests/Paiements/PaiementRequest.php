<?php

namespace App\Http\Requests\Paiements;

use Illuminate\Foundation\Http\FormRequest;

class PaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_carte' => ['nullable', 'string'],
            'date_expiration' => ['nullable', 'string'],
            'cvv' => ['nullable', 'string'],
            'nom_titulaire' => ['nullable', 'string', 'max:100'],
        ];
    }
}
