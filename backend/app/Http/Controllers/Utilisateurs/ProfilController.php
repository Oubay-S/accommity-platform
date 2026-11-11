<?php

namespace App\Http\Controllers\Utilisateurs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilisateurs\UpdateProfilRequest;
use App\Services\Utilisateurs\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfilController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json(['profil' => null]);
    }

    public function update(UpdateProfilRequest $request): JsonResponse
    {
        return response()->json(['message' => 'Profil mis à jour']);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        return response()->json(['photo_url' => '']);
    }
}
