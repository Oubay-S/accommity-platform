<?php

namespace App\Http\Requests\Evaluations;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'reservation_id' => ['required', 'exists:reservations,id'],
            'note_globale' => ['required', 'integer', 'min:1', 'max:5'],
            'commentaire' => ['required', 'string', 'min:10'],
        ];
    }
}
