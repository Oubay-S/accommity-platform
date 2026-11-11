<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class RepondreReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:accepter,refuser'],
            'motif' => ['nullable', 'string', 'required_if:decision,refuser'],
        ];
    }
}
