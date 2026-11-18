<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Administration\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAvisController extends Controller
{
    public function __construct(protected AdminService $adminService) {}

    /**
     * GET /api/admin/avis/signales
     * Récupère la liste paginée des évaluations signalées.
     */
    public function signales(Request $request): JsonResponse
    {
        $filters = [
            'recherche' => $request->query('recherche'),
        ];
        $perPage = (int) $request->query('par_page', 15);
        $avis = $this->adminService->getAvisSignales($filters, $perPage);

        return ApiResponse::success([
            'items' => $avis->items(),
            'pagination' => [
                'current_page' => $avis->currentPage(),
                'last_page' => $avis->lastPage(),
                'per_page' => $avis->perPage(),
                'total' => $avis->total(),
            ],
        ], 'Liste des avis signalés récupérée.');
    }

    /**
     * PUT /api/admin/avis/{id}/ignorer
     * Lève le signalement d'un avis après vérification.
     */
    public function ignorer(string $id): JsonResponse
    {
        try {
            $avis = $this->adminService->ignorerSignalementAvis($id);
            return ApiResponse::success($avis, 'Le signalement a été ignoré et l\'avis est maintenu en ligne.');
        } catch (\Exception $e) {
            return ApiResponse::error('Avis introuvable ou erreur de traitement.', 404);
        }
    }

    /**
     * DELETE /api/admin/avis/{id}
     * Supprime définitivement un avis non conforme.
     */
    public function supprimer(string $id): JsonResponse
    {
        try {
            $this->adminService->supprimerAvis($id);
            return ApiResponse::success(null, 'L\'avis signalé a été supprimé avec succès.');
        } catch (\Exception $e) {
            return ApiResponse::error('Avis introuvable ou erreur de suppression.', 404);
        }
    }
}
