<?php

namespace App\Http\Requests\Annonces;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mise à jour des champs d'une annonce (RG07).
 *
 * Les photos passent par les endpoints dédiés (`POST`/`DELETE .../photos`)
 * car PHP ne parse pas les fichiers sur une requête `PUT`.
 */
class UpdateAnnonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'type_logement' => ['sometimes', 'required', 'string', Rule::in(config('hebergement.types_logement', []))],
            'adresse' => ['sometimes', 'required', 'string', 'max:255'],
            'id_categorie' => ['sometimes', 'required', 'uuid', 'exists:categorie_geographiques,id_categorie'],
            'id_politique' => ['sometimes', 'required', 'uuid', 'exists:politique_annulations,id_politique'],
            'capacite' => ['sometimes', 'required', 'integer', 'min:1', 'max:50'],
            'tarif_nuit' => ['sometimes', 'required', 'numeric', 'min:50'],
            'mode_reservation' => ['sometimes', 'required', 'in:instantanee,sur_demande'],
            'equipements' => ['sometimes', 'nullable', 'array', 'max:20'],
            'equipements.*' => ['string', 'max:50'],
            'reglement_interieur' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $equipements = $this->input('equipements');

        if (is_string($equipements)) {
            $this->merge([
                'equipements' => array_values(array_filter(array_map('trim', explode(',', $equipements)))),
            ]);
        }
    }
}
