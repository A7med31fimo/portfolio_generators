<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'headline'     => fake()->sentence(6),
            'bio'          => fake()->paragraphs(2, true),
            'avatar'       => null,
            'hero_image'   => null,
            'location'     => fake()->city() . ', ' . fake()->country(),
            'phone'        => null,
            'github_url'   => 'https://github.com/' . fake()->userName(),
            'linkedin_url' => null,
            'twitter_url'  => null,
            'website_url'  => fake()->optional()->url(),
            'cv_url'       => null,
            'locale'       => fake()->randomElement(['en', 'ar']),
            'theme'        => 'classic',
            'accent_color' => '#00df9a',
            'is_published' => false,
        ];
    }

    /**
     * Published profile state.
     */
    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_published' => true,
            'headline'     => $attributes['headline'] ?? fake()->sentence(6),
            'bio'          => $attributes['bio'] ?? fake()->paragraphs(2, true),
        ]);
    }
}
