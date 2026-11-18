<?php

namespace App\Services\Annonces;

use App\Enums\StatutAnnonce;
use App\Enums\StatutReservation;
use App\Exceptions\ApiException;
use App\Models\Annonces\Annonce;
use App\Models\Annonces\CategorieGeographique;
use App\Models\Annonces\PolitiqueAnnulation;
use App\Repositories\Interfaces\AnnonceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnonceService
{
    public function __construct(protected AnnonceRepositoryInterface $annonceRepository) {}

    public function rechercherAnnonces(array $criteres)
    { /* TODO */
    }

    /* --------------------------------------------------------------------- */
    /* Espace hôte (RG06, RG07) */
    /* --------------------------------------------------------------------- */

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function creerAnnonce(string $hoteId, array $data, array $photos = []): Annonce
    {
        $donnees = $this->normaliserDonnees($data);
        $donnees['id_hote'] = $hoteId;
        $donnees['statut'] = StatutAnnonce::EN_ATTENTE->value;
        $donnees['photo_url'] = [];
        $donnees['equipements'] ??= '';

        return DB::transaction(function () use ($donnees, $photos) {
            $annonce = $this->annonceRepository->create($donnees);

            if ($photos !== []) {
                $urls = $this->stockerPhotos($annonce->getKey(), $photos);
                $annonce->forceFill(['photo_url' => $urls])->save();
            }

            return $this->chargerAnnonce($annonce->getKey());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function modifierAnnonce(string $id, array $data): Annonce
    {
        $annonce = $this->annonceRepository->findById($id);

        if (! $annonce) {
            throw new ApiException('Annonce introuvable.', 404, 'ANNONCE_INTROUVABLE');
        }

        $donnees = $this->normaliserDonnees($data);

        // On ne laisse jamais le client modifier le propriétaire, le statut ou les photos.
        unset($donnees['id_hote'], $donnees['statut'], $donnees['photo_url'], $donnees['photos']);

        if ($donnees !== []) {
            $annonce->fill($donnees)->save();
        }

        return $this->chargerAnnonce($id);
    }

    public function supprimerAnnonce(string $id): void
    {
        $annonce = $this->annonceRepository->findById($id);

        if (! $annonce) {
            throw new ApiException('Annonce introuvable.', 404, 'ANNONCE_INTROUVABLE');
        }

        if ($this->annonceRepository->aDesReservationsActives($id)) {
            throw new ApiException(
                'Cette annonce possède des réservations actives et ne peut pas être supprimée.',
                409,
                'ANNONCE_AYANT_RESERVATIONS',
            );
        }

        $this->supprimerFichiers($annonce->photo_url ?? []);

        $this->annonceRepository->delete($id);
    }

    /**
     * @param  array{statut?:string,recherche?:string}  $criteres
     * @return array{items: array, pagination: array, statistiques: array}
     */
    public function mesAnnonces(string $hoteId, array $criteres, int $parPage): array
    {
        $paginator = $this->annonceRepository->mesAnnonces($hoteId, $criteres, $parPage);

        return [
            'items' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'statistiques' => $this->annonceRepository->compterParStatut($hoteId),
        ];
    }

    public function getAnnonceHote(string $id, string $hoteId): ?Annonce
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        return $this->annonceRepository->findPourHote($id, $hoteId);
    }

    public function getAnnoncePourGestion(string $id): ?Annonce
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        return $this->annonceRepository->findById($id);
    }

    /**
     * Référentiels nécessaires au formulaire hôte.
     *
     * @return array<string, mixed>
     */
    public function getReferences(): array
    {
        return [
            'categories' => CategorieGeographique::query()
                ->orderBy('ville')
                ->get()
                ->map(fn (CategorieGeographique $categorie) => [
                    'id_categorie' => $categorie->getKey(),
                    'ville' => $categorie->ville,
                    'region' => $categorie->region,
                    'pays' => $categorie->pays,
                ])
                ->values()
                ->all(),
            'politiques' => PolitiqueAnnulation::query()
                ->orderBy('type_politique')
                ->get()
                ->map(fn (PolitiqueAnnulation $politique) => [
                    'id_politique' => $politique->getKey(),
                    'type_politique' => $politique->type_politique,
                    'description' => $politique->description,
                    'delai_remb_total' => (int) $politique->delai_remb_total,
                    'delai_remb_partiel' => (int) $politique->delai_remb_partiel,
                    'taux_remb_partiel' => (float) $politique->taux_remb_partiel,
                ])
                ->values()
                ->all(),
            'types_logement' => config('hebergement.types_logement', []),
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function ajouterPhotos(Annonce $annonce, array $photos): Annonce
    {
        $existantes = $annonce->photo_url ?? [];

        if (count($existantes) + count($photos) > 10) {
            throw new ApiException(
                'Une annonce ne peut pas contenir plus de 10 photos.',
                422,
                'TROP_DE_PHOTOS',
            );
        }

        $urls = DB::transaction(function () use ($annonce, $existantes, $photos) {
            $nouvelles = $this->stockerPhotos($annonce->getKey(), $photos);
            $annonce->forceFill(['photo_url' => array_values(array_merge($existantes, $nouvelles))])->save();

            return $annonce->photo_url;
        });

        return $this->chargerAnnonce($annonce->getKey());
    }

    public function supprimerPhoto(Annonce $annonce, string $url): Annonce
    {
        $existantes = $annonce->photo_url ?? [];

        if (! in_array($url, $existantes, true)) {
            throw new ApiException('Photo introuvable sur cette annonce.', 404, 'PHOTO_INTROUVABLE');
        }

        $restantes = array_values(array_filter($existantes, fn (string $photo) => $photo !== $url));

        $annonce->forceFill(['photo_url' => $restantes])->save();
        $this->supprimerFichiers([$url]);

        return $this->chargerAnnonce($annonce->getKey());
    }

    /* --------------------------------------------------------------------- */
    /* Catalogue public (Reda) — ne pas modifier */
    /* --------------------------------------------------------------------- */

    // DS1 [PORTED]
    public function rechercherAnnoncesPubliees(array $criteres, int $parPage): LengthAwarePaginator
    {
        return $this->annonceRepository->rechercherAnnoncesDisponibles($criteres, $parPage);
    }

    // [ADDED]
    public function getAnnoncesEnVedette(int $limite = 6): Collection
    {
        return $this->annonceRepository->getAnnoncesEnVedette($limite);
    }

    // DS2 [PORTED] : null si inexistante ou non publiée (l'ancien code levait une
    // Exception "Annonce non disponible ou inexistante.")
    public function getDetailsAnnonce(string $id): ?Annonce
    {
        if (! Str::isUuid($id)) {
            return null; // évite une erreur SQL 500 sur la colonne uuid PostgreSQL
        }
        $annonce = $this->annonceRepository->findDetailsById($id);

        return ($annonce && $annonce->statut === StatutAnnonce::PUBLIEE) ? $annonce : null;
    }

    // [PORTED] getDatesOccupees() verbatim, adapted to the new enums and the relation name `calendriers`
    public function getDatesOccupees(Annonce $annonce): array
    {
        $occupees = [];

        // 1. Réservations confirmées / en cours : nuits [arrivée, départ[
        foreach ($annonce->reservations()->whereIn('statut', [StatutReservation::CONFIRMEE->value, StatutReservation::EN_COURS->value])->get() as $res) {
            $curr = Carbon::parse($res->date_arrivee);
            $end = Carbon::parse($res->date_depart);
            while ($curr->lt($end)) {
                $occupees[] = $curr->format('Y-m-d');
                $curr->addDay();
            }
        }

        // 2. Blocages manuels : [début, fin] inclus
        foreach ($annonce->calendriers()->where('est_disponible', false)->get() as $cal) {
            $curr = Carbon::parse($cal->date_debut);
            $end = Carbon::parse($cal->date_fin);
            while ($curr->lte($end)) {
                $occupees[] = $curr->format('Y-m-d');
                $curr->addDay();
            }
        }

        $occupees = array_values(array_unique($occupees));
        sort($occupees);

        return $occupees;
    }

    /* --------------------------------------------------------------------- */
    /* Helpers */
    /* --------------------------------------------------------------------- */

    private function chargerAnnonce(string $id): Annonce
    {
        $annonce = $this->annonceRepository->findById($id);

        if ($annonce) {
            $annonce->load(['categorie', 'politiqueAnnulation'])
                ->loadCount([
                    'reservations as reservations_actives_count' => fn ($q) => $q->whereIn('statut', [
                        StatutReservation::CONFIRMEE->value,
                        StatutReservation::EN_COURS->value,
                    ]),
                ]);
        }

        return $annonce;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normaliserDonnees(array $data): array
    {
        foreach (['photos', 'statut', 'id_hote', 'photo_url'] as $cle) {
            unset($data[$cle]);
        }

        if (array_key_exists('equipements', $data)) {
            $equipements = $data['equipements'];

            if (is_array($equipements)) {
                $equipements = array_values(array_filter(array_map(
                    fn ($valeur) => trim((string) $valeur),
                    $equipements,
                ), fn (string $valeur) => $valeur !== ''));
                $data['equipements'] = implode(', ', $equipements);
            }
        }

        return $data;
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     * @return array<int, string>
     */
    private function stockerPhotos(string $annonceId, array $photos): array
    {
        $urls = [];

        try {
            foreach ($photos as $photo) {
                $chemin = $photo->store("annonces/{$annonceId}", 'public');
                $urls[] = url(Storage::disk('public')->url($chemin));
            }
        } catch (\Throwable $exception) {
            $this->supprimerFichiers($urls);

            throw $exception;
        }

        return $urls;
    }

    /**
     * @param  array<int, string>  $urls
     */
    private function supprimerFichiers(array $urls): void
    {
        foreach ($urls as $url) {
            if (preg_match('#/storage/(.+)$#', $url, $matches)) {
                Storage::disk('public')->delete($matches[1]);
            }
        }
    }
}
