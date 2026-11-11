<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Administration\AdminService;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function __construct(protected AdminService $adminService) {}

    public function index(): JsonResponse
    {
        return response()->json(['users' => []]);
    }

    public function toggleActif(string $id): JsonResponse
    {
        return response()->json(['message' => 'Statut utilisateur modifié']);
    }

    public function verifications(): JsonResponse
    {
        return response()->json(['verifications' => []]);
    }

    public function validerVerification(string $id): JsonResponse
    {
        return response()->json(['message' => 'Identité validée']);
    }

    public function rejeterVerification(string $id): JsonResponse
    {
        return response()->json(['message' => 'Identité rejetée']);
    }
}
