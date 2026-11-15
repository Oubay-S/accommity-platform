<?php

namespace App\Services\Utilisateurs;

use App\Models\Utilisateurs\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(protected UserRepositoryInterface $userRepository) {}

    /**
     * Inscription (RG01, RG02) : crée l'utilisateur et son profil dans une
     * transaction. Le rôle choisi à l'inscription alimente les drapeaux
     * `est_hote` / `est_voyageur` (la table n'a pas de colonne `role`).
     *
     * @param  array{nom:string,prenom:string,email:string,password:string,telephone?:?string,role:string,description?:?string}  $data
     */
    public function inscrire(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = $this->userRepository->create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'mot_de_passe' => $data['password'],
                'telephone' => $data['telephone'] ?? null,
                'est_hote' => $data['role'] === 'hote',
                'est_voyageur' => $data['role'] === 'voyageur',
                'est_actif' => true,
            ]);

            $user->profil()->create([
                'description' => $data['description'] ?? null,
            ]);

            return $user;
        });
    }

    /**
     * Vérifie les identifiants. Retourne null si l'email est inconnu ou le
     * mot de passe incorrect (le contrôleur décide du code HTTP).
     */
    public function authentifier(string $email, string $motDePasse): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || ! Hash::check($motDePasse, $user->getAuthPassword())) {
            return null;
        }

        return $user;
    }

    /** Charge les relations exposées par UserResource. */
    public function chargerProfilComplet(User $user): User
    {
        return $user->loadMissing(['profil', 'verificationIdentite']);
    }
}
