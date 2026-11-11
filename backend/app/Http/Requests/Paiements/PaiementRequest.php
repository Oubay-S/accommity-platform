<?php

namespace App\Http\Requests\Paiements;

use Illuminate\Foundation\Http\FormRequest;

class PaiementRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'numero_carte' => ['required', 'string'],
            'date_expiration' => ['required', 'string'],
            'cvv' => ['required', 'string', 'size:3'],
        ];
    }
}
