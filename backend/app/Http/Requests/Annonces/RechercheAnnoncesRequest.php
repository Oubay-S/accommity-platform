<?php

namespace App\Http\Requests\Annonces;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RechercheAnnoncesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->equipements)) {
            $items = array_values(array_filter(
                array_map('trim', explode(',', $this->equipements)),
                fn (string $v) => $v !== '',
            ));
            $this->merge(['equipements' => $items]);
        }
    }

    public function rules(): array
    {
        return [
            'ville' => ['nullable', 'string', 'max:100'],
            'id_categorie' => ['nullable', 'uuid'],
            'type_logement' => ['nullable', 'string', 'max:50'],
            'voyageurs' => ['nullable', 'integer', 'min:1', 'max:50'],
            'date_arrivee' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today', 'required_with:date_depart'],
            'date_depart' => ['nullable', 'date_format:Y-m-d', 'after:date_arrivee', 'required_with:date_arrivee'],
            'prix_min' => ['nullable', 'numeric', 'min:0'],
            'prix_max' => ['nullable', 'numeric', 'min:0', Rule::when($this->filled('prix_min'), 'gte:prix_min')],
            'equipements' => ['nullable', 'array'],
            'equipements.*' => ['string', 'max:50'],
            'tri' => ['nullable', 'in:recent,prix_asc,prix_desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_categorie.uuid' => 'La catégorie sélectionnée est invalide.',
            'voyageurs.integer' => 'Le nombre de voyageurs doit être un entier.',
            'voyageurs.min' => 'Le nombre de voyageurs doit être au moins 1.',
            'voyageurs.max' => 'Le nombre de voyageurs ne peut pas dépasser 50.',
            'date_arrivee.date_format' => 'La date d\'arrivée doit être au format AAAA-MM-JJ.',
            'date_arrivee.after_or_equal' => 'La date d\'arrivée ne peut pas être dans le passé.',
            'date_arrivee.required_with' => 'La date d\'arrivée est requise lorsque la date de départ est renseignée.',
            'date_depart.date_format' => 'La date de départ doit être au format AAAA-MM-JJ.',
            'date_depart.after' => 'La date de départ doit être postérieure à la date d\'arrivée.',
            'date_depart.required_with' => 'La date de départ est requise lorsque la date d\'arrivée est renseignée.',
            'prix_min.numeric' => 'Le prix minimum doit être un nombre.',
            'prix_min.min' => 'Le prix minimum ne peut pas être négatif.',
            'prix_max.numeric' => 'Le prix maximum doit être un nombre.',
            'prix_max.min' => 'Le prix maximum ne peut pas être négatif.',
            'prix_max.gte' => 'Le prix maximum doit être supérieur ou égal au prix minimum.',
            'tri.in' => 'Le critère de tri est invalide.',
        ];
    }
}
