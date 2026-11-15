<?php

namespace App\Services\Utilisateurs;

use App\Exceptions\ApiException;
use App\Models\Utilisateurs\Profil;
use App\Models\Utilisateurs\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

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

    /*
    |--------------------------------------------------------------------------
    | Profil (RG04)
    |--------------------------------------------------------------------------
    */

    public function profilOuCreer(User $user): Profil
    {
        return $user->profil()->firstOrCreate([]);
    }

    /**
     * Met à jour l'identité (nom, prénom, email, téléphone) et/ou la
     * biographie, puis retourne l'utilisateur avec ses relations à jour.
     *
     * @param  array{nom?:string,prenom?:string,email?:string,telephone?:?string,description?:?string}  $data
     */
    public function mettreAJourProfil(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $champsUtilisateur = Arr::only($data, ['nom', 'prenom', 'email', 'telephone']);

            if ($champsUtilisateur !== []) {
                $this->userRepository->update($user->getKey(), $champsUtilisateur);
            }

            if (array_key_exists('description', $data)) {
                $this->profilOuCreer($user)->update([
                    'description' => $data['description'],
                ]);
            }

            return $user->refresh()->loadMissing(['profil', 'verificationIdentite']);
        });
    }

    /**
     * Change le mot de passe et révoque les autres sessions (on conserve le
     * token courant pour ne pas déconnecter l'utilisateur immédiatement).
     */
    public function changerMotDePasse(User $user, string $actuel, string $nouveau): void
    {
        if (! Hash::check($actuel, $user->getAuthPassword())) {
            throw new ApiException(
                'Mot de passe actuel incorrect.',
                422,
                'MOT_DE_PASSE_ACTUEL_INVALIDE',
            );
        }

        $tokenCourant = $user->currentAccessToken();

        $user->update(['mot_de_passe' => $nouveau]);

        $tokens = $user->tokens();

        if ($tokenCourant instanceof PersonalAccessToken) {
            $tokens->where('id', '!=', $tokenCourant->getKey());
        }

        $tokens->delete();
    }

    /**
     * La photo est stockée sur le disque public (`storage/app/public/profils`)
     * et servie via `/storage/profils/...`. L'ancienne photo est supprimée.
     */
    public function enregistrerPhotoProfil(User $user, UploadedFile $photo): Profil
    {
        return DB::transaction(function () use ($user, $photo): Profil {
            $profil = $this->profilOuCreer($user);
            $anciennePhoto = $profil->photo_url;

            $chemin = $photo->store('profils', 'public');

            $profil->update(['photo_url' => $chemin]);

            if ($anciennePhoto !== null && $anciennePhoto !== $chemin) {
                Storage::disk('public')->delete($anciennePhoto);
            }

            return $profil->refresh();
        });
    }
}
