<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware vérifiant que l'utilisateur est un hôte validé (RG03)
 */
class VerifyHote
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->estHoteVerifie()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès restreint aux hôtes avec identité vérifiée.'
            ], Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}
