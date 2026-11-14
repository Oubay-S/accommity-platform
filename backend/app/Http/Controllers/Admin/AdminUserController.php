<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Utilisateurs\VerificationIdentiteResource;
use App\Http\Responses\ApiResponse;
use App\Models\Utilisateurs\VerificationIdentite;
use App\Services\Administration\AdminService;
use App\Services\Utilisateurs\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController extends Controller
{
    public function __construct(
        protected AdminService $adminService,
        protected VerificationService $verificationService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['users' => []]);
    }

    public function toggleActif(string $id): JsonResponse
    {
        return response()->json(['message' => 'Statut utilisateur modifié']);
    }

    /** GET /api/admin/verifications?statut=en_cours&recherche=amina */
    public function verifications(Request $request): JsonResponse
    {
        $statut = $request->string('statut', 'tous')->toString();
        $recherche = trim($request->string('recherche')->toString());
        $autorises = [
            VerificationIdentite::STATUT_EN_COURS,
            VerificationIdentite::STATUT_VALIDEE,
            VerificationIdentite::STATUT_REJETEE,
        ];

        $base = VerificationIdentite::query();

        if ($recherche !== '') {
            $like = '%'.mb_strtolower($recherche).'%';

            $base->whereHas('utilisateur', function ($query) use ($like): void {
                $query->whereRaw('LOWER(nom) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(prenom) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
            });
        }

        $stats = [
            'tous' => (clone $base)->count(),
            'en_cours' => (clone $base)->where('statut', VerificationIdentite::STATUT_EN_COURS)->count(),
            'validee' => (clone $base)->where('statut', VerificationIdentite::STATUT_VALIDEE)->count(),
            'rejetee' => (clone $base)->where('statut', VerificationIdentite::STATUT_REJETEE)->count(),
        ];

        $query = (clone $base)
            ->with('utilisateur')
            ->latest('date_soumission');

        if (in_array($statut, $autorises, true)) {
            $query->where('statut', $statut);
        }

        $parPage = max(1, min($request->integer('par_page', 10), 50));
        $paginator = $query->paginate($parPage);

        return ApiResponse::success([
            'items' => VerificationIdentiteResource::collection($paginator->getCollection())->resolve(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'stats' => $stats,
        ], 'Demandes de vérification récupérées.');
    }

    /** PUT /api/admin/verifications/{id}/valider */
    public function validerVerification(Request $request, string $id): JsonResponse
    {
        $verification = $this->verificationService->valider($id, $request->user());
        $verification->loadMissing('utilisateur');

        return ApiResponse::success(
            ['verification' => (new VerificationIdentiteResource($verification))->resolve()],
            'Identité validée.',
        );
    }

    /** PUT /api/admin/verifications/{id}/rejeter */
    public function rejeterVerification(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'motif' => ['required', 'string', 'max:500'],
        ]);

        $verification = $this->verificationService->rejeter(
            $id,
            $validated['motif'],
            $request->user(),
        );
        $verification->loadMissing('utilisateur');

        return ApiResponse::success(
            ['verification' => (new VerificationIdentiteResource($verification))->resolve()],
            'Identité rejetée.',
        );
    }

    /** GET /api/admin/verifications/{id}/document — document privé (loi 09-08) */
    public function document(string $id): StreamedResponse
    {
        return $this->verificationService->telecharger(
            VerificationIdentite::findOrFail($id),
        );
    }
}
