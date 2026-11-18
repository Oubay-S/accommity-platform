<?php

namespace App\Http\Requests\Evaluations;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_reservation' => ['required', 'uuid', 'exists:reservations,id_reservation'],
            'note' => ['required', 'integer', 'min:1', 'max:5'],
            'commentaire' => ['required', 'string', 'min:10', 'max:2000'],
            'notes_detaillees' => ['nullable', 'array'],
            'notes_detaillees.proprete' => ['required_with:notes_detaillees', 'numeric', 'min:1', 'max:5'],
            'notes_detaillees.communication' => ['required_with:notes_detaillees', 'numeric', 'min:1', 'max:5'],
            'notes_detaillees.emplacement' => ['required_with:notes_detaillees', 'numeric', 'min:1', 'max:5'],
            'notes_detaillees.rapport_qualite_prix' => ['required_with:notes_detaillees', 'numeric', 'min:1', 'max:5'],
            'notes_detaillees.exactitude' => ['required_with:notes_detaillees', 'numeric', 'min:1', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_reservation.required' => 'La réservation est obligatoire.',
            'note.required' => 'La note globale est obligatoire.',
            'commentaire.min' => 'Le commentaire doit contenir au moins 10 caractères.',
        ];
    }
}
