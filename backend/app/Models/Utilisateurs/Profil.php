<?php

namespace App\Models\Utilisateurs;

use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
    protected $fillable = ['user_id', 'photo_url', 'bio', 'adresse', 'ville', 'pays'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
