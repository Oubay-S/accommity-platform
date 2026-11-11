<?php

namespace App\Http\Requests\Annonces;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnonceRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['titre' => ['sometimes', 'string']]; }
}
