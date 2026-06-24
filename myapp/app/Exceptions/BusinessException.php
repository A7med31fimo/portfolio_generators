<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Thrown when a business rule is violated.
 * Always returns a 422 JSON response.
 *
 * Usage:
 *   throw new BusinessException('Username already taken.');
 *   throw new BusinessException('Account suspended.', 'ACCOUNT_SUSPENDED', 403);
 */
class BusinessException extends Exception
{
    public function __construct(
        string $message = 'A business rule was violated.',
        private readonly string $code = 'BUSINESS_ERROR',
        int $statusCode = 422
    ) {
        parent::__construct($message, $statusCode);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $this->getMessage(),
            'errors'  => ['code' => $this->code],
        ], $this->getCode());
    }
}
