<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'bio'        => $this->bio,
            'headline'   => $this->headline,
            'avatar'     => $this->avatar_url, // uses the accessor for full URL
            'location'   => $this->location,
            'phone'      => $this->phone,
            'locale'     => $this->locale,
            'theme'      => $this->theme,

            // Phase 3+ fields (null until populated)
            'hero_image'   => $this->hero_image,
            'github_url'   => $this->github_url,
            'linkedin_url' => $this->linkedin_url,
            'twitter_url'  => $this->twitter_url,
            'website_url'  => $this->website_url,
            'cv_url'       => $this->cv_url,
            'accent_color' => $this->accent_color ?? '#00df9a',
            'is_published' => (bool) $this->is_published,

            'updated_at'   => $this->updated_at->toISOString(),
        ];
    }
}
