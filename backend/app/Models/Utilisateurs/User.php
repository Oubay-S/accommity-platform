<?php

namespace App\Models\Utilisateurs;

use App\Models\Administration\Administrateur;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $table = 'utilisateurs';
    protected $primaryKey = 'id_utilisateur';

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = null;

    /** The password column is "mot_de_passe" (Laravel 11+ feature). */
    protected $authPasswordName = 'mot_de_passe';

    /** The table has no remember_token column (API tokens only). */
    protected $rememberTokenName = '';

    protected $fillable = [
        'nom', 'prenom', 'email', 'mot_de_passe', 'telephone',
        'est_hote', 'est_voyageur', 'est_actif',
    ];

    protected $hidden = ['mot_de_passe'];

    protected function casts(): array
    {
        return [
            'mot_de_passe' => 'hashed',
            'est_hote' => 'boolean',
            'est_voyageur' => 'boolean',
            'est_actif' => 'boolean',
            'date_creation' => 'datetime',
        ];
    }

    public function profil()
    {
        return $this->hasOne(Profil::class, 'id_utilisateur');
    }

    /** Latest identity verification request (a host may resubmit after a rejection). */
    public function verificationIdentite()
    {
        // Not latestOfMany(): it runs MAX() on the UUID primary key, which PostgreSQL doesn't support.
        return $this->hasOne(VerificationIdentite::class, 'id_utilisateur')->latest('date_soumission');
    }

    /** Toutes les demandes de vérification (historique). */
    public function verifications()
    {
        return $this->hasMany(VerificationIdentite::class, 'id_utilisateur');
    }

    public function administrateur()
    {
        return $this->hasOne(Administrateur::class, 'id_utilisateur');
    }

    public function estHote(): bool
    {
        return (bool) $this->est_hote;
    }

    public function estVoyageur(): bool
    {
        return (bool) $this->est_voyageur;
    }

    public function estHoteVerifie(): bool
    {
        // RG03 : l'hôte est vérifié dès qu'au moins une de ses demandes a été
        // validée. Se fier à la « dernière » demande serait faux : après une
        // nouvelle soumission (ou en cas d'égalité de date_soumission), un
        // dossier en cours masquerait une validation déjà acquise.
        return $this->estHote() && $this->verifications()
            ->where('statut', VerificationIdentite::STATUT_VALIDEE)
            ->exists();
    }

    public function estAdmin(): bool
    {
        return $this->administrateur()->exists();
    }
}
