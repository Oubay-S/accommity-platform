<?php

namespace App\Http\Controllers\Utilisateurs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilisateurs\UpdateProfilPhotoRequest;
use App\Http\Requests\Utilisateurs\UpdateProfilRequest;
use App\Http\Resources\Utilisateurs\ProfilResource;
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

    /** PUT /api/profil */
    public function update(UpdateProfilRequest $request): JsonResponse
    {
        $profil = $this->userService->mettreAJourProfil($request->user(), $request->validated());

        return ApiResponse::success(
            ['profil' => (new ProfilResource($profil))->resolve()],
            'Profil mis à jour.',
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
