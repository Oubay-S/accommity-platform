<?php

namespace App\Repositories\Eloquent;

use App\Models\Utilisateurs\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id) { return User::find($id); }
    public function findByEmail(string $email) { return User::where('email', $email)->first(); }
    public function create(array $data) { return User::create($data); }
    public function update(int $id, array $data) { return tap(User::findOrFail($id))->update($data); }
}
