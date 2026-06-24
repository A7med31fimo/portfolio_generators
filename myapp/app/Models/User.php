<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden from serialization.
     * Never expose password or tokens in API responses.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * A user has one profile (always exists — created on registration).
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * A user has many Sanctum tokens.
     * Defined by HasApiTokens — listed here for clarity.
     */

    // Phase 3+ relationships (uncomment as needed):
    // public function projects(): HasMany
    // {
    //     return $this->hasMany(Project::class);
    // }

    // public function skills(): HasMany
    // {
    //     return $this->hasMany(Skill::class);
    // }

    // public function experiences(): HasMany
    // {
    //     return $this->hasMany(Experience::class);
    // }

    // ─── Accessors & Mutators ────────────────────────────────────────────────

    /**
     * Always store usernames lowercase.
     */
    public function setUsernameAttribute(string $value): void
    {
        $this->attributes['username'] = strtolower(trim($value));
    }

    /**
     * Always store emails lowercase.
     */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Check if the user's email has been verified.
     */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Check if the user is on the Pro plan.
     * Extend this when the subscriptions table is added in Phase 5.
     */
    public function isPro(): bool
    {
        // Phase 5: return $this->subscription?->isActive() ?? false;
        return false;
    }

    /**
     * Send the email verification notification.
     *
     * Overrides the default to use our custom VerifyEmail notification,
     * which points the link at the Next.js frontend instead of this API's
     * own URL — see App\Notifications\VerifyEmail for the full explanation.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }
}
