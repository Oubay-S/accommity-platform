<?php

namespace App\Http\Controllers\Utilisateurs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilisateurs\SubmitVerificationRequest;
use App\Http\Resources\Utilisateurs\VerificationIdentiteResource;
use App\Http\Responses\ApiResponse;
use App\Services\Utilisateurs\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vérification d'identité des hôtes (RG03).
 *
 * Le document est privé : seul l'administrateur peut le consulter via
 * un endpoint dédié du back-office.
 */
class VerificationIdentiteController extends Controller
{
    public function __construct(protected VerificationService $verificationService) {}

    /** POST /api/profil/verification */
    public function submit(SubmitVerificationRequest $request): JsonResponse
    {
        $verification = $this->verificationService->soumettre(
            $request->user(),
            $request->validated(),
            $request->file('document'),
        );

        return ApiResponse::success(
            ['verification' => (new VerificationIdentiteResource($verification))->resolve()],
            'Demande de vérification soumise.',
            201,
        );
    }

    /** GET /api/profil/verification/statut */
    public function status(Request $request): JsonResponse
    {
        $verification = $this->verificationService->derniere($request->user());

        return ApiResponse::success(
            ['verification' => $verification === null
                ? null
                : (new VerificationIdentiteResource($verification))->resolve()],
            'Statut de vérification récupéré.',
        );
    }
}
