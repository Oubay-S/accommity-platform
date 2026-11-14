<?php

namespace App\Models\Utilisateurs;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'profils';
    protected $primaryKey = 'id_profil';
    public $timestamps = false;

    protected $fillable = ['id_utilisateur', 'photo_url', 'description', 'note_moyenne'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_utilisateur');
    }
}
