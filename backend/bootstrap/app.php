<?php

use App\Exceptions\ApiException;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\ForceJsonResponse::class);
        $middleware->alias([
            'role.hote' => \App\Http\Middleware\VerifyHote::class,
            'role.admin' => \App\Http\Middleware\VerifyAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isApi = fn (Request $request): bool => $request->is('api/*');

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                'Les données fournies sont invalides.',
                422,
                $e->errors(),
                'VALIDATION_ECHOUEE',
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                'Authentification requise. Token absent ou expiré.',
                401,
                errorCode: 'NON_AUTHENTIFIE',
            );
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage() ?: 'Accès refusé.',
                403,
                errorCode: 'ACCES_REFUSE',
            );
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                'Trop de tentatives. Réessayez dans un instant.',
                429,
                errorCode: 'TROP_DE_TENTATIVES',
            )->withHeaders($e->getHeaders());
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                'Ressource introuvable.',
                404,
                errorCode: 'INTROUVABLE',
            );
        });

        $exceptions->render(function (ApiException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode(),
                errorCode: $e->apiCode,
            );
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage() ?: 'La requête a échoué.',
                $e->getStatusCode(),
            );
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $payload = ApiResponse::error('Une erreur interne est survenue.', 500, errorCode: 'ERREUR_INTERNE');

            if (config('app.debug')) {
                $payload = response()->json([
                    'success' => false,
                    'message' => 'Une erreur interne est survenue.',
                    'error_code' => 'ERREUR_INTERNE',
                    'debug' => [
                        'type' => $e::class,
                        'message' => $e->getMessage(),
                    ],
                ], 500);
            }

            return $payload;
        });
    })->create();
