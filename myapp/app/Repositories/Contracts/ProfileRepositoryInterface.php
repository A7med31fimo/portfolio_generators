<?php

namespace App\Repositories\Contracts;

use App\Models\Profile;

interface ProfileRepositoryInterface
{
    public function findByUserId(int $userId): ?Profile;

    public function create(array $data): Profile;

    public function update(int $userId, array $data): Profile;

    public function findPublishedByUsername(string $username): ?Profile;
}
