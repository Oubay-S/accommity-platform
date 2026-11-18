<?php

namespace App\Repositories\Interfaces;

use App\Models\Annonces\Annonce;

interface AnnonceRepositoryInterface
{
    public function findById(string $id);
    public function search(array $criteres);
    public function create(array $data);
    public function update(string $id, array $data);
    public function delete(string $id);
    public function rechercherAnnoncesDisponibles(array $criteres, int $parPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    public function getAnnoncesEnVedette(int $limite): \Illuminate\Database\Eloquent\Collection;
    public function findDetailsById(string $id): ?\App\Models\Annonces\Annonce;

    /** Annonces d'un hôte, filtrables par statut / recherche. */
    public function mesAnnonces(string $hoteId, array $criteres, int $parPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /** Annonce appartenant à un hôte (quel que soit son statut). */
    public function findPourHote(string $id, string $hoteId): ?Annonce;

    /** Compteur d'annonces par statut pour un hôte. */
    public function compterParStatut(string $hoteId): array;

    /** Vrai si l'annonce a une réservation confirmée ou en cours. */
    public function aDesReservationsActives(string $id): bool;
}
