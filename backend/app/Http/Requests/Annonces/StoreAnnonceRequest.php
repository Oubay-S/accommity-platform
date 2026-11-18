<?php

namespace App\Http\Requests\Annonces;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création d'une annonce par un hôte vérifié (RG06).
 *
 * Les noms de champs suivent le schéma PostgreSQL réel :
 * `tarif_nuit`, `capacite`, `id_categorie`, `id_politique`.
 */
class StoreAnnonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estHoteVerifie() ?? false;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'type_logement' => ['required', 'string', Rule::in(config('hebergement.types_logement', []))],
            'adresse' => ['required', 'string', 'max:255'],
            'id_categorie' => ['required', 'uuid', 'exists:categorie_geographiques,id_categorie'],
            'id_politique' => ['required', 'uuid', 'exists:politique_annulations,id_politique'],
            'capacite' => ['required', 'integer', 'min:1', 'max:50'],
            'tarif_nuit' => ['required', 'numeric', 'min:50'],
            'mode_reservation' => ['required', 'in:instantanee,sur_demande'],
            'equipements' => ['nullable', 'array', 'max:20'],
            'equipements.*' => ['string', 'max:50'],
            'reglement_interieur' => ['nullable', 'string', 'max:2000'],
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
