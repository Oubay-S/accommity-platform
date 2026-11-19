<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('motif_annulation') && !$this->has('motif')) {
            $this->merge(['motif' => $this->input('motif_annulation')]);
        }
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
