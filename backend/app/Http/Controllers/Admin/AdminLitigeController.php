<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Administration\LitigeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLitigeController extends Controller
{
    public function __construct(protected LitigeService $litigeService) {}

    public function index(): JsonResponse
    {
        return response()->json(['litiges' => []]);
    }

    public function resoudre(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Litige résolu']);
    }
}
