<?php

namespace App\Http\Requests\Annonces;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Blocage / déblocage manuel d'une plage inclusive [date_debut, date_fin] (RG08, RG09).
 */
class BloquerDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'date_debut' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'date_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_debut'],
        ];
    }
}
