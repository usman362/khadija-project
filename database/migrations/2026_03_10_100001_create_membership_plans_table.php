<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('billing_cycle')->default('monthly'); // monthly, quarterly, yearly, one_time
            $table->integer('duration_days')->nullable(); // null = unlimited
            $table->integer('max_events')->nullable(); // null = unlimited
            $table->integer('max_bookings')->nullable(); // null = unlimited
            $table->boolean('has_chat')->default(true);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('badge_text')->nullable(); // e.g. "Most Popular", "Best Value"
            $table->string('badge_color')->nullable(); // e.g. "primary", "success", "warning"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
