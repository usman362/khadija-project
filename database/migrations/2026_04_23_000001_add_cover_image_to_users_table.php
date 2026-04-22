<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a profile cover image (banner) column to users.
 *
 * Lives on `users` alongside `avatar` because both are personal identity
 * images tied to the account, not role-specific profile data. Keeps the
 * accessor pattern (avatar_url / cover_image_url) consistent.
 *
 * Nullable string — stores a relative path under the `public` disk, same
 * as avatar. Empty means "show placeholder gradient".
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};
