<?php

namespace App\Repositories\Interfaces;

use App\Models\Annonces\Annonce;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AnnonceRepositoryInterface
{
    public function findById(string $id);

    public function search(array $criteres);

    public function create(array $data);

    public function update(string $id, array $data);

    public function delete(string $id);

    public function rechercherAnnoncesDisponibles(array $criteres, int $parPage): LengthAwarePaginator;

    public function getAnnoncesEnVedette(int $limite): Collection;

    public function findDetailsById(string $id): ?Annonce;

    /** Annonces d'un hôte, filtrables par statut / recherche. */
    public function mesAnnonces(string $hoteId, array $criteres, int $parPage): LengthAwarePaginator;

    /** Annonce appartenant à un hôte (quel que soit son statut). */
    public function findPourHote(string $id, string $hoteId): ?Annonce;

    /** Compteur d'annonces par statut pour un hôte. */
    public function compterParStatut(string $hoteId): array;

    /** Vrai si l'annonce a une réservation confirmée ou en cours. */
    public function aDesReservationsActives(string $id): bool;
}
