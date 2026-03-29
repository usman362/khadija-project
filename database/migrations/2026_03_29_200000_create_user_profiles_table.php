<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add avatar column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('phone', 30)->nullable()->after('avatar');
        });

        // Create user_profiles table for extended profile data
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // ── Common Fields ──
            $table->text('bio')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable(); // {linkedin, twitter, facebook, instagram}

            // ── Client-Specific Fields ──
            $table->string('company_name')->nullable();
            $table->string('company_website')->nullable();
            $table->string('industry', 100)->nullable();
            $table->json('event_preferences')->nullable(); // preferred event types, budget range, etc.

            // ── Professional/Supplier-Specific Fields ──
            $table->string('headline')->nullable(); // tagline / professional title
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->string('availability', 30)->nullable(); // available, busy, not_available
            $table->json('skills')->nullable(); // array of skill strings
            $table->integer('experience_years')->nullable();
            $table->json('portfolio')->nullable(); // array of {title, url, description}
            $table->json('certifications')->nullable(); // array of {name, issuer, year}
            $table->json('languages')->nullable(); // array of {language, level}

            // ── Notification Preferences ──
            $table->boolean('notify_email_bookings')->default(true);
            $table->boolean('notify_email_messages')->default(true);
            $table->boolean('notify_email_events')->default(true);
            $table->boolean('notify_email_marketing')->default(false);

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'phone']);
        });
    }
};
