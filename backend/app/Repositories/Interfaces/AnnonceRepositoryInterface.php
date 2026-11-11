<?php

namespace App\Repositories\Interfaces;

interface AnnonceRepositoryInterface
{
    public function findById(int $id);
    public function search(array $criteres);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
