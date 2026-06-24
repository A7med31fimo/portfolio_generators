<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Portfolio content
            $table->string('headline', 120)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();           // storage path or full URL
            $table->string('hero_image')->nullable();

            // Contact & location
            $table->string('location', 100)->nullable();
            $table->string('phone', 30)->nullable();

            // Social links
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('cv_url')->nullable();

            // Portfolio settings
            $table->string('locale', 10)->default('en');
            $table->string('theme', 30)->default('classic');
            $table->string('accent_color', 20)->default('#00df9a');
            $table->boolean('is_published')->default(false);

            $table->timestamps();

            // Each user has exactly one profile
            $table->unique('user_id');
            $table->index(['user_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
