<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Exceptions that should not be reported to logs.
     */
    protected $dontReport = [];

    /**
     * Exceptions that are never flashed to the session.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Phase 3+: send to Sentry / Bugsnag
            // if (app()->bound('sentry')) { app('sentry')->captureException($e); }
        });

        // Override render for all API requests to always return JSON
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $this->handleApiException($e, $request);
            }
        });
    }

    /**
     * Convert any exception to a consistent JSON envelope for API consumers.
     */
    private function handleApiException(Throwable $e, Request $request): JsonResponse
    {
        // 422 — Validation failures
        if ($e instanceof ValidationException) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // 401 — Unauthenticated
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated. Please log in.',
                'errors'  => [],
            ], 401);
        }

        // 404 — Eloquent model not found
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return response()->json([
                'status'  => false,
                'message' => "{$model} not found.",
                'errors'  => [],
            ], 404);
        }

        // 404 — Route not found
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'status'  => false,
                'message' => 'The requested endpoint does not exist.',
                'errors'  => [],
            ], 404);
        }

        // 405 — Method not allowed
        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'status'  => false,
                'message' => 'HTTP method not allowed for this endpoint.',
                'errors'  => [],
            ], 405);
        }

        // Generic HTTP exceptions (403, 429, etc.)
        if ($e instanceof HttpException) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage() ?: 'HTTP error.',
                'errors'  => [],
            ], $e->getStatusCode());
        }

        // 500 — Unexpected server error
        $message = app()->environment('production')
            ? 'An unexpected error occurred. Please try again.'
            : $e->getMessage();

        return response()->json([
            'status'  => false,
            'message' => $message,
            'errors'  => [],
        ], 500);
    }
}
