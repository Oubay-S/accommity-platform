<?php

namespace App\Repositories\Interfaces;

interface AnnonceRepositoryInterface
{
    public function findById(string $id);
    public function search(array $criteres);
    public function create(array $data);
    public function update(string $id, array $data);
    public function delete(string $id);
}
