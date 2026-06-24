<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            // username: unique, lowercase, URL-friendly slug
            // e.g. nextdev.io/janesmith
            $table->string('username', 30)->unique();

            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->timestamps();

            // No separate index() calls needed — unique() on username and email
            // above already creates an index on each column in MySQL.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
