<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['motif' => ['required', 'string']]; }
}
