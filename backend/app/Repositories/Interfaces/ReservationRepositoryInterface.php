<?php

namespace App\Repositories\Interfaces;

interface ReservationRepositoryInterface
{
    public function findById(int $id);
    public function getParVoyageur(int $voyageurId);
    public function getParHote(int $hoteId);
    public function create(array $data);
    public function updateStatut(int $id, string $statut);
}
