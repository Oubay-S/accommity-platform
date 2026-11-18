<?php

namespace App\Http\Requests\Annonces;

use Illuminate\Foundation\Http\FormRequest;

class AjouterPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
