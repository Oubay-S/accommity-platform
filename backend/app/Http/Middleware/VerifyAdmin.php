<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware vérifiant le rôle administrateur de la plateforme
 */
class VerifyAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->estAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès restreint à l\'administration.'
            ], Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}
