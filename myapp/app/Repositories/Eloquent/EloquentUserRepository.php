<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly User $model) {}

    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model
            ->where('email', strtolower(trim($email)))
            ->first();
    }

    public function findByUsername(string $username): ?User
    {
        return $this->model
            ->where('username', strtolower(trim($username)))
            ->first();
    }

    public function emailExists(string $email): bool
    {
        return $this->model
            ->where('email', strtolower(trim($email)))
            ->exists();
    }

    public function usernameExists(string $username): bool
    {
        // Cache the result for 60 seconds to reduce DB hits from
        // the live username-availability check on the register form
        return Cache::remember(
            'username_exists_' . strtolower($username),
            60,
            fn() => $this->model
                ->whereRaw('LOWER(username) = ?', [strtolower(trim($username))])
                ->exists()
        );
    }

    public function create(array $data): User
    {
        $user = $this->model->create($data);

        // Bust the username cache so it's immediately unavailable
        Cache::forget('username_exists_' . strtolower($data['username']));

        return $user;
    }

    public function update(int $id, array $data): User
    {
        $user = $this->model->findOrFail($id);
        $user->update($data);

        return $user->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->destroy($id);
    }

    public function findWithProfile(int $id): ?User
    {
        return $this->model
            ->with('profile')
            ->find($id);
    }
}
