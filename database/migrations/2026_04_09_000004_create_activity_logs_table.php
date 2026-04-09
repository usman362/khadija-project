<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // User this action belongs to (nullable for failed logins where user may not exist)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Action identifier e.g. login, logout, login_failed, password_changed, password_reset
            $table->string('action', 50);

            // Optional identifier for the actor when user_id is null (email attempted, etc.)
            $table->string('subject_identifier')->nullable();

            // Request context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Optional extra context (JSON)
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
