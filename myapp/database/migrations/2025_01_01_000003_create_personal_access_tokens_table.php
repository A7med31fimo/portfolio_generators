<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sanctum personal access tokens table.
 * This is the standard Sanctum migration — kept explicit so it runs
 * in the correct order relative to the users table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');             // polymorphic: tokenable_type + tokenable_id
            $table->string('name');                  // device name
            $table->string('token', 64)->unique();   // hashed token
            $table->text('abilities')->nullable();   // JSON array of abilities
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // No manual index needed here — morphs('tokenable') above already
            // creates an index on (tokenable_type, tokenable_id) automatically.
            // Adding one explicitly caused: "Duplicate key name ..." on MySQL.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
