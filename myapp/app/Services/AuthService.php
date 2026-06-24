<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface    $userRepository,
        private readonly ProfileRepositoryInterface $profileRepository,
        private readonly UserService               $userService,
    ) {}

    // ─── Registration ──────────────────────────────────────────────────────

    /**
     * Register a new user, create their profile, and fire the Registered event.
     * Everything runs in a single DB transaction — atomically or not at all.
     *
     * @param  array{name: string, username: string, email: string, password: string}  $data
     * @throws BusinessException
     */
    public function register(array $data): User
    {
        // Belt-and-suspenders checks (FormRequest already validated these,
        // but services should be self-contained and testable in isolation)
        if ($this->userRepository->emailExists($data['email'])) {
            throw new BusinessException('An account with this email already exists.', 'EMAIL_TAKEN', 409);
        }

        if ($this->userRepository->usernameExists($data['username'])) {
            throw new BusinessException('This username is already taken.', 'USERNAME_TAKEN', 409);
        }

        if ($this->userService->isUsernameReserved($data['username'])) {
            throw new BusinessException('This username is reserved.', 'USERNAME_RESERVED', 422);
        }

        $user = DB::transaction(function () use ($data): User {
            // Create user — password is auto-hashed by the 'hashed' cast on the model
            $user = $this->userRepository->create([
                'name'     => trim($data['name']),
                'username' => strtolower(trim($data['username'])),
                'email'    => strtolower(trim($data['email'])),
                'password' => $data['password'],
            ]);

            // Always create the profile record alongside the user.
            // Profile always exists — no null checks needed downstream.
            $this->profileRepository->create([
                'user_id' => $user->id,
                'locale'  => $data['locale'] ?? 'en',
                'theme'   => 'classic',
            ]);

            return $user->load('profile');
        });

        Log::info('User registered', ['user_id' => $user->id, 'email' => $user->email]);

        // Fire Registered (triggers the verification email) AFTER the
        // transaction has committed — and never let a mail failure roll
        // back or fail the registration itself. If the mail server is
        // down/misconfigured, the account still exists; the user can
        // request a new verification email later via
        // POST /auth/email/verification-notification.
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::warning('Verification email failed to send during registration', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return $user;
    }

    // ─── Login ─────────────────────────────────────────────────────────────

    /**
     * Validate credentials and issue a Sanctum token.
     *
     * @param  array{email: string, password: string, remember: bool, device_name: string}  $data
     * @throws BusinessException
     * @return array{user: User, token: string, expires_at: string|null}
     */
    public function login(array $data): array
    {
        $user = $this->userRepository->findByEmail($data['email']);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // Generic message — don't reveal whether email exists
            throw new BusinessException(
                'These credentials do not match our records.',
                'INVALID_CREDENTIALS',
                401
            );
        }

        $deviceName = $data['device_name'] ?? 'web';
        $rememberMe = $data['remember'] ?? false;

        // Expiration: null = never (remember me), or configured minutes
        $expiresAt = $rememberMe
            ? null
            : now()->addMinutes(config('sanctum.expiration', 43200));

        // Create a named token — useful for the "active sessions" screen later
        $token = $user->createToken(
            name:      $deviceName,
            abilities: ['*'],
            expiresAt: $expiresAt,
        )->plainTextToken;

        Log::info('User logged in', ['user_id' => $user->id, 'device' => $deviceName]);

        return [
            'user'       => $user->load('profile'),
            'token'      => $token,
            'expires_at' => $expiresAt?->toISOString(),
        ];
    }

    // ─── Logout ────────────────────────────────────────────────────────────

    /**
     * Revoke the current token (logout current device) or all tokens (logout everywhere).
     */
    public function logout(User $user, bool $allDevices = false): void
    {
        if ($allDevices) {
            $user->tokens()->delete();
            Log::info('User logged out of all devices', ['user_id' => $user->id]);
        } else {
            // Revoke only the token used for this request
            $user->currentAccessToken()->delete();
            Log::info('User logged out', ['user_id' => $user->id]);
        }
    }

    // ─── Password Reset ────────────────────────────────────────────────────

    /**
     * Send a password reset link to the given email.
     * Returns true even if the email isn't registered — prevents email enumeration.
     */
    public function sendPasswordResetLink(string $email): bool
    {
        $status = Password::sendResetLink(['email' => strtolower($email)]);

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Password reset link sent', ['email' => $email]);
            return true;
        }

        // Log internally but don't surface the real status to the caller
        Log::warning('Password reset link request', ['email' => $email, 'status' => $status]);

        // Always return true to prevent email enumeration attacks
        return true;
    }

    /**
     * Reset the user's password using a valid reset token.
     *
     * @param  array{email: string, token: string, password: string}  $data
     * @throws BusinessException
     */
    public function resetPassword(array $data): bool
    {
        $status = Password::reset(
            [
                'email'    => strtolower($data['email']),
                'token'    => $data['token'],
                'password' => $data['password'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all tokens — forces re-login after password change
                $user->tokens()->delete();

                event(new PasswordReset($user));

                Log::info('Password reset completed', ['user_id' => $user->id]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new BusinessException(
                'Invalid or expired password reset token. Please request a new one.',
                'INVALID_RESET_TOKEN',
                422
            );
        }

        return true;
    }
}
