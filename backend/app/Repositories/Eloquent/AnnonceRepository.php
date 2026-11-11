<?php

namespace App\Repositories\Eloquent;

use App\Models\Annonces\Annonce;
use App\Repositories\Interfaces\AnnonceRepositoryInterface;

class AnnonceRepository implements AnnonceRepositoryInterface
{
    public function findById(int $id) { return Annonce::find($id); }
    public function search(array $criteres) { return Annonce::query()->paginate(15); }
    public function create(array $data) { return Annonce::create($data); }
    public function update(int $id, array $data) { return tap(Annonce::findOrFail($id))->update($data); }
    public function delete(int $id) { return Annonce::destroy($id); }
}
