<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Contract for User data access.
 * Controllers and Services depend on this interface — never on the
 * Eloquent implementation directly. Swap implementations freely.
 */
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function emailExists(string $email): bool;

    public function usernameExists(string $username): bool;

    public function create(array $data): User;

    public function update(int $id, array $data): User;

    public function delete(int $id): bool;

    /**
     * Find a user with their profile pre-loaded.
     */
    public function findWithProfile(int $id): ?User;
}
