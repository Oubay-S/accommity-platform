<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exception métier destinée à l'API : porte un code HTTP et un `error_code`
 * stable que le frontend Angular peut interpréter.
 *
 * Exemple : new ApiException('Déjà vérifié.', 409, 'DEJA_VERIFIE');
 */
class ApiException extends HttpException
{
    public function __construct(
        string $message,
        int $statusCode = 400,
        public readonly ?string $apiCode = null,
        array $headers = [],
    ) {
        parent::__construct($statusCode, $message, null, $headers);
    }
}
