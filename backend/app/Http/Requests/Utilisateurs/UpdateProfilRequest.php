<?php

namespace App\Http\Requests\Utilisateurs;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['bio' => ['nullable', 'string']]; }
}
