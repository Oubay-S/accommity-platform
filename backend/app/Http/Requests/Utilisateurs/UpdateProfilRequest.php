<?php

namespace App\Http\Requests\Utilisateurs;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `note_moyenne` est calculée par le package Évaluations (RG30),
            // elle n'est jamais modifiable par l'utilisateur.
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
