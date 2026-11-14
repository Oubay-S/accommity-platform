<?php

namespace App\Models\Administration;

use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administrateur extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'administrateurs';
    protected $primaryKey = 'id_admin';
    public $timestamps = false;

    protected $fillable = ['id_utilisateur', 'derniere_connexion'];

    protected function casts(): array
    {
        return [
            'derniere_connexion' => 'datetime',
        ];
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'id_utilisateur');
    }
}
