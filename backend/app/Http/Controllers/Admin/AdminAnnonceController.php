<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Administration\AdminService;
use Illuminate\Http\JsonResponse;

class AdminAnnonceController extends Controller
{
    public function __construct(protected AdminService $adminService) {}

    public function index(): JsonResponse
    {
        return response()->json(['annonces' => []]);
    }

    public function approuver(string $id): JsonResponse
    {
        return response()->json(['message' => 'Annonce approuvée']);
    }

    public function suspendre(string $id): JsonResponse
    {
        return response()->json(['message' => 'Annonce suspendue']);
    }
}
