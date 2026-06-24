<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Config;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface    $userRepository,
        private readonly ProfileRepositoryInterface $profileRepository,
    ) {}

    /**
     * Check if a username is available.
     * Validates format, reserved words, and DB uniqueness.
     *
     * Returns an array with:
     *   available: bool
     *   reason:    string|null — why it's unavailable
     */
    public function checkUsernameAvailability(string $username): array
    {
        $username = strtolower(trim($username));

        // Length
        if (strlen($username) < 3) {
            return ['available' => false, 'reason' => 'too_short'];
        }

        if (strlen($username) > 30) {
            return ['available' => false, 'reason' => 'too_long'];
        }

        // Format: only lowercase letters, numbers, hyphens
        if (! preg_match('/^[a-z0-9-]+$/', $username)) {
            return ['available' => false, 'reason' => 'invalid_chars'];
        }

        // No leading/trailing hyphens
        if (str_starts_with($username, '-') || str_ends_with($username, '-')) {
            return ['available' => false, 'reason' => 'invalid_format'];
        }

        // Reserved usernames from config/api.php
        $reserved = Config::get('api.reserved_usernames', []);
        if (in_array($username, $reserved, true)) {
            return ['available' => false, 'reason' => 'reserved'];
        }

        // DB uniqueness check
        if ($this->userRepository->usernameExists($username)) {
            return ['available' => false, 'reason' => 'taken'];
        }

        return ['available' => true, 'reason' => null];
    }

    /**
     * Determine if a registration username is valid and unreserved.
     * Used by RegisterRequest for the custom 'not_reserved' rule.
     */
    public function isUsernameReserved(string $username): bool
    {
        $reserved = Config::get('api.reserved_usernames', []);
        return in_array(strtolower($username), $reserved, true);
    }

    /**
     * Get a user by ID with their profile eager-loaded.
     */
    public function getUserWithProfile(int $id): ?User
    {
        return $this->userRepository->findWithProfile($id);
    }
}
