<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shared influencer-program content library (Resources section): guides,
     * videos, templates, checklists, tools, webinars, articles, and courses.
     * Global (not per-influencer) — every influencer sees the same library.
     */
    public function up(): void
    {
        Schema::create('influencer_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('body')->nullable();              // for articles
            $table->string('type')->default('guide');          // guide|video|template|checklist|tool|webinar|article|course
            $table->string('category')->nullable();
            $table->string('level')->nullable();               // beginner|intermediate|advanced (courses)
            $table->unsignedInteger('lessons')->default(0);    // courses
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('downloads')->default(0);
            $table->string('badge')->nullable();               // new|popular|trending|template|webinar
            $table->string('url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencer_resources');
    }
};
