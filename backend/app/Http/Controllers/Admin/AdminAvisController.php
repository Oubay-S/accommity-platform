<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Administration\AdminService;
use Illuminate\Http\JsonResponse;

class AdminAvisController extends Controller
{
    public function __construct(protected AdminService $adminService) {}

    public function signales(): JsonResponse
    {
        return response()->json(['avis' => []]);
    }

    public function supprimer(string $id): JsonResponse
    {
        return response()->json(['message' => 'Avis supprimé']);
    }
}
