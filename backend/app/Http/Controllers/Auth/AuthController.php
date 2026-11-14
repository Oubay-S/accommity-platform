<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\Utilisateurs\UserResource;
use App\Models\Utilisateurs\User;
use App\Services\Utilisateurs\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Authentification API par Bearer Token (RG01, RG02).
 */
class AuthController extends Controller
{
    public function __construct(protected UserService $userService) {}

    /** POST /api/auth/register — 201 Created */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userService->inscrire($request->validated());
        $user = $this->userService->chargerProfilComplet($user);

        return ApiResponse::success(
            $this->authPayload($user),
            'Compte créé avec succès.',
            201,
        );
    }

    /** POST /api/auth/login — 200 OK */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userService->authentifier(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        if ($user === null) {
            return ApiResponse::error(
                'Email ou mot de passe incorrect.',
                401,
                errorCode: 'IDENTIFIANTS_INVALIDES',
            );
        }

        // RG05 : un compte désactivé par l'administrateur ne peut plus se connecter.
        if (! $user->est_actif) {
            return ApiResponse::error(
                'Ce compte a été désactivé. Contactez l\'administrateur.',
                403,
                errorCode: 'COMPTE_INACTIF',
            );
        }

        if ($user->estAdmin()) {
            $user->administrateur?->forceFill(['derniere_connexion' => now()])->save();
        }

        $user = $this->userService->chargerProfilComplet($user);

        return ApiResponse::success(
            $this->authPayload($user),
            'Connexion réussie.',
        );
    }

    /** POST /api/auth/logout — révoque le token courant */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return ApiResponse::success(null, 'Déconnexion réussie.');
    }

    /** GET /api/auth/me — profil complet de l'utilisateur connecté */
    public function me(Request $request): JsonResponse
    {
        $user = $this->userService->chargerProfilComplet($request->user());

        return ApiResponse::success(
            ['user' => (new UserResource($user))->resolve()],
            'Utilisateur authentifié.',
        );
    }

    /** @return array{user: array<string, mixed>, token: string, token_type: string} */
    private function authPayload(User $user): array
    {
        return [
            'user' => (new UserResource($user))->resolve(),
            'token' => $user->createToken('angular')->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
