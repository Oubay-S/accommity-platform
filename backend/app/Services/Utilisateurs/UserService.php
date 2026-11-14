<?php

namespace App\Services\Utilisateurs;

use App\Repositories\Interfaces\UserRepositoryInterface;

class UserService
{
    public function __construct(protected UserRepositoryInterface $userRepository) {}

    public function creerUtilisateur(array $data) { /* TODO */ }
    public function modifierProfil(string $userId, array $data) { /* TODO */ }
}
