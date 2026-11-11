<?php

namespace App\Models\Utilisateurs;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\RoleUtilisateur;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nom', 'prenom', 'email', 'password', 'telephone', 'role', 'is_actif'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => RoleUtilisateur::class,
        'is_actif' => 'boolean'
    ];

    public function profil()
    {
        return $this->hasOne(Profil::class, 'user_id');
    }

    public function verificationIdentite()
    {
        return $this->hasOne(VerificationIdentite::class, 'user_id');
    }

    public function estHote(): bool
    {
        return $this->role === RoleUtilisateur::HOTE;
    }

    public function estHoteVerifie(): bool
    {
        return $this->estHote() && $this->verificationIdentite?->estValidee();
    }

    public function estAdmin(): bool
    {
        return $this->role === RoleUtilisateur::ADMIN;
    }
}
