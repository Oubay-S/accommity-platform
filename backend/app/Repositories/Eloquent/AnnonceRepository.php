<?php

namespace App\Repositories\Eloquent;

use App\Models\Annonces\Annonce;
use App\Repositories\Interfaces\AnnonceRepositoryInterface;

class AnnonceRepository implements AnnonceRepositoryInterface
{
    public function findById(string $id) { return Annonce::find($id); }
    public function search(array $criteres) { return Annonce::query()->paginate(15); }
    public function create(array $data) { return Annonce::create($data); }
    public function update(string $id, array $data) { return tap(Annonce::findOrFail($id))->update($data); }
    public function delete(string $id) { return Annonce::destroy($id); }
}
