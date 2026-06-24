<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a User model into the API response shape.
 * Never exposes: password, remember_token, or internal IDs indirectly.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'username'          => $this->username,
            'email'             => $this->email,
            'email_verified'    => $this->email_verified_at !== null,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at'        => $this->created_at->toISOString(),

            // Include profile if it's been loaded (eager or via withProfile scope)
            'profile' => $this->whenLoaded('profile', fn() =>
                new ProfileResource($this->profile)
            ),
        ];
    }
}
