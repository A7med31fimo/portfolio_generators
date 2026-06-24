<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'headline',
        'avatar',
        'location',
        'phone',
        'locale',
        'theme',
        // Phase 2+ fields
        'hero_image',
        'cv_url',
        'github_url',
        'linkedin_url',
        'twitter_url',
        'website_url',
        'accent_color',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * A profile belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Return the full public URL for the avatar.
     * Falls back to a generated initials URL if no avatar is set.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        // If it's already a full URL (e.g. OAuth provider image), return as-is
        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        return asset('storage/' . $this->avatar);
    }
}
