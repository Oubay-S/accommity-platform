<?php

namespace App\Repositories\Interfaces;

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
}
