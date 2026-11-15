<?php

namespace App\Http\Controllers\Utilisateurs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilisateurs\ChangePasswordRequest;
use App\Http\Requests\Utilisateurs\UpdateProfilPhotoRequest;
use App\Http\Requests\Utilisateurs\UpdateProfilRequest;
use App\Http\Resources\Utilisateurs\ProfilResource;
use App\Http\Resources\Utilisateurs\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\Utilisateurs\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Profil public de l'utilisateur connecté (RG04).
 */
class ProfilController extends Controller
{
    public function __construct(protected UserService $userService) {}

    /** GET /api/profil */
    public function show(Request $request): JsonResponse
    {
        $profil = $this->userService->profilOuCreer($request->user());

        return ApiResponse::success(
            ['profil' => (new ProfilResource($profil))->resolve()],
            'Profil récupéré.',
        );
    }

    /** PUT /api/profil — identité (nom, prénom, email, téléphone) + biographie */
    public function update(UpdateProfilRequest $request): JsonResponse
    {
        $user = $this->userService->mettreAJourProfil($request->user(), $request->validated());

        return ApiResponse::success([
            'user' => (new UserResource($user))->resolve(),
            'profil' => $user->profil === null
                ? null
                : (new ProfilResource($user->profil))->resolve(),
        ], 'Profil mis à jour.');
    }

    /** PUT /api/profil/password */
    public function changerMotDePasse(ChangePasswordRequest $request): JsonResponse
    {
        $this->userService->changerMotDePasse(
            $request->user(),
            $request->string('current_password')->toString(),
            $request->string('password')->toString(),
        );

        return ApiResponse::success(
            null,
            'Mot de passe mis à jour. Les autres sessions ont été déconnectées.',
        );
    }

    /** POST /api/profil/photo */
    public function uploadPhoto(UpdateProfilPhotoRequest $request): JsonResponse
    {
        $profil = $this->userService->enregistrerPhotoProfil(
            $request->user(),
            $request->file('photo'),
        );

        return ApiResponse::success(
            ['profil' => (new ProfilResource($profil))->resolve()],
            'Photo de profil mise à jour.',
        );
    }
}
