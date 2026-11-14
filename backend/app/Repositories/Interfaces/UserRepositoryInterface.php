<?php

namespace App\Repositories\Interfaces;

interface UserRepositoryInterface
{
    public function findById(string $id);
    public function findByEmail(string $email);
    public function create(array $data);
    public function update(string $id, array $data);
}
