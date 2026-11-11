<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Utilisateurs\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur d'authentification (RG01, RG02)
 */
class AuthController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        // TODO: Inscription nouvel utilisateur (voyageur / hôte)
        return response()->json(['message' => 'Inscription réussie']);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // TODO: Authentification & génération de token Sanctum
        return response()->json(['token' => 'bearer_token_placeholder']);
    }

    public function logout(Request $request): JsonResponse
    {
        // TODO: Révocation du token courant
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }
}
