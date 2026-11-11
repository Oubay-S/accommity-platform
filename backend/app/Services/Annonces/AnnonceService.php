<?php

namespace App\Services\Annonces;

use App\Repositories\Interfaces\AnnonceRepositoryInterface;

class AnnonceService
{
    public function __construct(protected AnnonceRepositoryInterface $annonceRepository) {}

    public function rechercherAnnonces(array $criteres) { /* TODO */ }
    public function creerAnnonce(int $hoteId, array $data) { /* TODO */ }
    public function modifierAnnonce(int $id, array $data) { /* TODO */ }
    public function supprimerAnnonce(int $id) { /* TODO */ }
}
