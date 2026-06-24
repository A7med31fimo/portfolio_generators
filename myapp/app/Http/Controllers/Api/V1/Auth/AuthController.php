<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserService $userService,
    ) {}

    // ─── POST /api/v1/auth/register ──────────────────────────────────────────

    /**
     * Register a new user account.
     *
     * @response 201 {
     *   "status": true,
     *   "message": "Account created successfully.",
     *   "data": { "user": {}, "token": "...", "expires_at": null }
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        // Auto-issue a token on registration so the client is immediately authenticated
        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Account created successfully.',
            'data'    => [
                'user'       => new UserResource($user),
                'token'      => $token,
                'expires_at' => now()->addMinutes(config('sanctum.expiration', 43200))->toISOString(),
            ],
        ], 201);
    }

    // ─── POST /api/v1/auth/login ─────────────────────────────────────────────

    /**
     * Authenticate with credentials and receive a Sanctum token.
     *
     * @bodyParam email    string required
     * @bodyParam password string required
     * @bodyParam remember boolean optional  — if true, token never expires
     * @bodyParam device_name string optional — label for this token
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Login successful.",
     *   "data": { "user": {}, "token": "...", "expires_at": "..." }
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'Login successful.',
            'data'    => [
                'user'       => new UserResource($result['user']),
                'token'      => $result['token'],
                'expires_at' => $result['expires_at'],
            ],
        ]);
    }

    // ─── POST /api/v1/auth/logout ────────────────────────────────────────────

    /**
     * Revoke the current token (or all tokens if ?all_devices=true).
     * Requires: Authorization: Bearer {token}
     *
     * @response 200 { "status": true, "message": "Logged out successfully.", "data": null }
     */
    public function logout(Request $request): JsonResponse
    {
        $allDevices = $request->boolean('all_devices', false);

        $this->authService->logout($request->user(), $allDevices);

        return response()->json([
            'status'  => true,
            'message' => $allDevices
                ? 'Logged out from all devices successfully.'
                : 'Logged out successfully.',
            'data'    => null,
        ]);
    }

    // ─── GET /api/v1/auth/me ────────────────────────────────────────────────

    /**
     * Return the currently authenticated user with their profile.
     * Requires: Authorization: Bearer {token}
     *
     * @response 200 { "status": true, "message": "OK", "data": { "user": {} } }
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->userService->getUserWithProfile($request->user()->id);

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    // ─── POST /api/v1/auth/forgot-password ──────────────────────────────────

    /**
     * Send a password reset link to the given email.
     * Always returns 200 — prevents email enumeration.
     *
     * @response 200 { "status": true, "message": "If this email exists, a reset link has been sent.", "data": null }
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->validated('email'));

        return response()->json([
            'status'  => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.',
            'data'    => null,
        ]);
    }

    // ─── POST /api/v1/auth/reset-password ───────────────────────────────────

    /**
     * Reset the user's password using a valid token from the reset email.
     *
     * @bodyParam token    string required — from reset email link
     * @bodyParam email    string required
     * @bodyParam password string required
     * @bodyParam password_confirmation string required
     *
     * @response 200 { "status": true, "message": "Password reset successfully. Please log in.", "data": null }
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'Password reset successfully. Please log in with your new password.',
            'data'    => null,
        ]);
    }

    // ─── GET /api/v1/auth/email/verify/{id}/{hash} ──────────────────────────

    /**
     * Verify the user's email via the signed link sent by Laravel's
     * built-in VerifyEmail notification.
     *
     * Requires: Authorization: Bearer {token} AND a valid signed URL
     * (both 'auth:sanctum' and 'signed' middleware guard this route).
     *
     * @response 200 { "status": true, "message": "Email verified successfully.", "data": null }
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        // The {id} route param must match the authenticated user —
        // prevents one user's link from verifying a different account.
        if ((string) $user->getKey() !== (string) $request->route('id')) {
            return response()->json([
                'status'  => false,
                'message' => 'This verification link does not belong to your account.',
                'errors'  => [],
            ], 403);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid verification link.',
                'errors'  => [],
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status'  => true,
                'message' => 'Email already verified.',
                'data'    => null,
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'status'  => true,
            'message' => 'Email verified successfully.',
            'data'    => null,
        ]);
    }

    // ─── POST /api/v1/auth/email/verification-notification ─────────────────

    /**
     * Resend the verification email.
     * Requires: Authorization: Bearer {token}
     *
     * @response 200 { "status": true, "message": "Verification email sent.", "data": null }
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status'  => true,
                'message' => 'Email already verified.',
                'data'    => null,
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'status'  => true,
            'message' => 'Verification email sent.',
            'data'    => null,
        ]);
    }

    // ─── GET /api/v1/auth/check-username ────────────────────────────────────

    /**
     * Public endpoint — check if a username is available.
     * Called with debounce from the Next.js register form.
     *
     * @queryParam username string required
     *
     * @response 200 { "status": true, "message": "OK", "data": { "available": true, "reason": null } }
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $username = strtolower(trim($request->query('username', '')));

        if (empty($username)) {
            return response()->json([
                'status'  => false,
                'message' => 'username query parameter is required.',
                'errors'  => ['username' => ['Username is required.']],
            ], 400);
        }

        $result = $this->userService->checkUsernameAvailability($username);

        return response()->json([
            'status'  => true,
            'message' => $result['available'] ? 'Username is available.' : 'Username is not available.',
            'data'    => $result,
        ]);
    }
}
