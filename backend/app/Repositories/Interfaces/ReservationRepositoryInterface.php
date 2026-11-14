<?php

namespace App\Repositories\Interfaces;

interface ReservationRepositoryInterface
{
    public function findById(string $id);
    public function getParVoyageur(string $voyageurId);
    public function getParHote(string $hoteId);
    public function create(array $data);
    public function updateStatut(string $id, string $statut);
}
