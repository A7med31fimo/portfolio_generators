<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Deterministic dev/test account ───────────────────────────────────
        $dev = User::firstOrCreate(
            ['email' => 'dev@nextdev.io'],
            [
                'name'              => 'Dev User',
                'username'          => 'devuser',
                'email'             => 'dev@nextdev.io',
                'password'          => Hash::make('Password1!'),
                'email_verified_at' => now(),
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $dev->id],
            [
                'headline'     => 'Full-Stack Developer',
                'bio'          => 'Building the future of developer portfolios.',
                'location'     => 'Cairo, Egypt',
                'github_url'   => 'https://github.com/devuser',
                'locale'       => 'en',
                'theme'        => 'classic',
                'is_published' => true,
            ]
        );

        // ── Random test users with profiles ──────────────────────────────────
        User::factory()
            ->count(10)
            ->withProfile()
            ->create();

        $this->command->info('✅ Users seeded — dev@nextdev.io / Password1!');
    }
}
