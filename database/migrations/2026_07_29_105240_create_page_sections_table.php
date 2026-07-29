<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable content for the public pages.
 *
 * One row per section of a page. The fixed parts of a section — its heading,
 * lead paragraph, main image — get their own columns; the repeating parts (the
 * five How-It-Works steps, the three assistance cards) live in `payload`, whose
 * shape is declared per section in config/page-sections.php.
 *
 * Layout and markup stay in the Blade template, so this makes the words and the
 * pictures editable, not the page structure — an admin cannot break the design.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page', 60)->default('landing');
            $table->string('key', 60);
            $table->string('heading')->nullable();
            $table->string('subheading', 500)->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['page', 'key']);
            $table->index(['page', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
