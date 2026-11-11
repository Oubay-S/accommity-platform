<?php

namespace App\Repositories\Interfaces;

interface PaiementRepositoryInterface
{
    public function findById(int $id);
    public function create(array $data);
    public function getHistoriqueUtilisateur(int $userId);
}
