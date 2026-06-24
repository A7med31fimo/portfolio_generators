<?php

namespace App\Repositories\Eloquent;

use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;

class EloquentProfileRepository implements ProfileRepositoryInterface
{
    public function __construct(private readonly Profile $model) {}

    public function findByUserId(int $userId): ?Profile
    {
        return $this->model
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $data): Profile
    {
        return $this->model->create($data);
    }

    public function update(int $userId, array $data): Profile
    {
        $profile = $this->model
            ->where('user_id', $userId)
            ->firstOrFail();

        $profile->update($data);

        return $profile->fresh();
    }

    public function findPublishedByUsername(string $username): ?Profile
    {
        return $this->model
            ->whereHas('user', fn($q) => $q->where('username', strtolower($username)))
            ->where('is_published', true)
            ->with('user')
            ->first();
    }
}
