<?php

namespace App\Repositories\Eloquent;

use App\Models\Paiements\Paiement;
use App\Repositories\Interfaces\PaiementRepositoryInterface;

class PaiementRepository implements PaiementRepositoryInterface
{
    public function findById(int $id) { return Paiement::find($id); }
    public function create(array $data) { return Paiement::create($data); }
    public function getHistoriqueUtilisateur(int $userId) { return Paiement::all(); }
}
