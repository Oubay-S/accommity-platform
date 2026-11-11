<?php

namespace App\Http\Controllers\Utilisateurs;

use App\Http\Controllers\Controller;
use App\Services\Utilisateurs\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function show(string $id): JsonResponse
    {
        return response()->json(['user' => null]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Utilisateur mis à jour']);
    }
}
