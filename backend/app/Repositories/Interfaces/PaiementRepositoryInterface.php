<?php

namespace App\Repositories\Interfaces;

interface PaiementRepositoryInterface
{
    public function findById(string $id);
    public function create(array $data);
    public function getHistoriqueUtilisateur(string $userId);
}
