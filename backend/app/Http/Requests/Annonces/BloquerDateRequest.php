<?php

namespace App\Http\Requests\Annonces;

use Illuminate\Foundation\Http\FormRequest;

class BloquerDateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['dates' => ['required', 'array']]; }
}
