<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Default state — generates a random realistic user.
     */
    public function definition(): array
    {
        $name     = fake()->name();
        $username = strtolower(Str::slug(fake()->unique()->userName(), '-'));

        // Ensure username is URL-friendly (max 30 chars, no leading/trailing hyphens)
        $username = substr(preg_replace('/^-+|-+$/', '', $username), 0, 30);

        return [
            'name'              => $name,
            'username'          => $username,
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make('Password1!'), // use a known password for testing
            'remember_token'    => Str::random(10),
        ];
    }

    /**
     * Indicate the user has an unverified email.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create the user with their profile automatically.
     */
    public function withProfile(): static
    {
        return $this->afterCreating(function (User $user) {
            Profile::factory()->for($user)->create();
        });
    }
}
