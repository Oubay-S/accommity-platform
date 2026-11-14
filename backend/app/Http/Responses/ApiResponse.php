<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Enveloppe JSON unique de l'API Accommity.
 *
 * Succès :   { "success": true,  "message": "...", "data": {...} }
 * Erreurs :  { "success": false, "message": "...", "errors": {...}, "error_code": "..." }
 */
final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Opération réussie.',
        int $status = 200,
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    public static function error(
        string $message,
        int $status = 400,
        ?array $errors = null,
        ?string $errorCode = null,
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($errorCode !== null) {
            $payload['error_code'] = $errorCode;
        }

        return response()->json($payload, $status);
    }
}
