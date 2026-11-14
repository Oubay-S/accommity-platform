<?php

namespace App\Models\Administration;

use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Administrateur extends Model
{
    use HasUuids;

    protected $table = 'administrateurs';
    protected $primaryKey = 'id_admin';
    public $timestamps = false;

    protected $fillable = ['id_utilisateur', 'email', 'mot_de_passe', 'derniere_connexion'];
    protected $hidden = ['mot_de_passe'];

    protected function casts(): array
    {
        return [
            'mot_de_passe' => 'hashed',
            'derniere_connexion' => 'datetime',
        ];
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'id_utilisateur');
    }
}
