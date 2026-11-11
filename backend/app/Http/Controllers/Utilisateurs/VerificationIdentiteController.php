<?php

namespace App\Http\Controllers\Utilisateurs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilisateurs\SubmitVerificationRequest;
use App\Services\Utilisateurs\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur de vérification d'identité des hôtes (RG03)
 */
class VerificationIdentiteController extends Controller
{
    public function __construct(protected VerificationService $verificationService) {}

    public function submit(SubmitVerificationRequest $request): JsonResponse
    {
        // TODO: Dépôt pièce d'identité (CIN/Passeport)
        return response()->json(['message' => 'Demande de vérification soumise']);
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json(['statut' => 'en_attente']);
    }
}
