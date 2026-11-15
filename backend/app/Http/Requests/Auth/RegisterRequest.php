<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('utilisateurs', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            // RG03 : un hôte doit fournir un numéro de téléphone.
            'telephone' => ['nullable', 'string', 'max:20', 'required_if:role,hote'],
            'role' => ['required', Rule::in(['voyageur', 'hote'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }
}
