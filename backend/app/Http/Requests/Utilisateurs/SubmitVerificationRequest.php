<?php

namespace App\Http\Requests\Utilisateurs;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVerificationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'type_document' => ['required', 'in:cin,passeport'],
            'numero_document' => ['required', 'string'],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
