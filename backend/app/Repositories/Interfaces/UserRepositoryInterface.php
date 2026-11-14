<?php

namespace App\Repositories\Interfaces;

use App\Models\Utilisateurs\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(array $data): User;

    public function update(string $id, array $data): User;
}
