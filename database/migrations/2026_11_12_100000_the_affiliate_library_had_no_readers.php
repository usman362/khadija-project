<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Resources section of the affiliate portal is gone (owner's call,
 * 2026-11-12: no value in it). This drops its storage in the same pass.
 *
 * Leaving the table behind was the tempting option, and this project has
 * already paid for that once: the Co-Op removal of 07.16 left three "dormant,
 * harmless" package columns and two weeks later the database still held eight
 * rows of a product that no longer existed. Invisible, because the markup that
 * read them had already been deleted, which is exactly why it survived.
 *
 * Nothing is lost here that a seeder cannot put back: every row was generic
 * guide and course content, global to the programme, with no affiliate's own
 * records in it and no foreign key pointing at it.
 *
 * down() recreates the table so the 2026_06_23 migration can still roll back
 * in order, but not the content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('influencer_resources');
    }

    public function down(): void
    {
        Schema::create('influencer_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('body')->nullable();
            $table->string('type')->default('guide');
            $table->string('category')->nullable();
            $table->string('level')->nullable();
            $table->unsignedInteger('lessons')->default(0);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('downloads')->default(0);
            $table->string('badge')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'is_featured']);
        });
    }
};
