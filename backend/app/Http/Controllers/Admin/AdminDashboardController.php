<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Administration\AdminService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __construct(protected AdminService $adminService) {}

    public function stats(): JsonResponse
    {
        return response()->json(['stats' => []]);
    }
}
