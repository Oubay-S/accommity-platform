<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class RepondreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Supporte 'acceptee' (bool), 'decision' ou 'action' ('accepter'/'refuser')
        if ($this->has('action') && ! $this->has('decision')) {
            $this->merge(['decision' => $this->input('action')]);
        }
        if ($this->has('acceptee') && ! $this->has('decision')) {
            $this->merge(['decision' => $this->boolean('acceptee') ? 'accepter' : 'refuser']);
        }
        if ($this->has('motif_refus') && ! $this->has('motif')) {
            $this->merge(['motif' => $this->input('motif_refus')]);
        }
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:accepter,refuser'],
            'motif' => ['nullable', 'string', 'required_if:decision,refuser', 'max:1000'],
        ];
    }

    public function isAcceptee(): bool
    {
        return $this->input('decision') === 'accepter' || $this->boolean('acceptee');
    }

    public function getMotif(): ?string
    {
        return $this->input('motif') ?? $this->input('motif_refus');
    }
}
