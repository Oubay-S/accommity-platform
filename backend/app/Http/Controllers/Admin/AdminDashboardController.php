<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Administration\AdminService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __construct(protected AdminService $adminService) {}

    /**
     * GET /api/admin/stats
     * Retourne tous les KPIs globaux pour le tableau de bord administrateur.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->adminService->getStatistiquesGlobales();
        return ApiResponse::success($stats, 'Statistiques administratives récupérées.');
    }

    /**
     * GET /api/admin/reservations
     * Supervision de toutes les réservations de la plateforme.
     */
    public function reservations(\Illuminate\Http\Request $request): JsonResponse
    {
        $filters = [
            'statut' => $request->query('statut', 'tous'),
            'recherche' => $request->query('recherche'),
        ];
        $perPage = (int) $request->query('par_page', 15);
        $reservations = $this->adminService->getReservations($filters, $perPage);

        return ApiResponse::success([
            'items' => $reservations->items(),
            'pagination' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'total' => $reservations->total(),
            ],
        ], 'Liste des réservations récupérée.');
    }

    /**
     * GET /api/admin/finances
     * Supervision des flux financiers (paiements, versements, remboursements).
     */
    public function finances(\Illuminate\Http\Request $request): JsonResponse
    {
        $type = $request->query('type', 'paiements');
        $filters = [
            'statut' => $request->query('statut', 'tous'),
            'motif' => $request->query('motif', 'tous'),
        ];
        $perPage = (int) $request->query('par_page', 15);
        $transactions = $this->adminService->getTransactionsFinancieres($type, $filters, $perPage);

        return ApiResponse::success([
            'items' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], 'Flux financiers récupérés.');
    }
}
