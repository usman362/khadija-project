<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which service categories a professional actually offers.
 *
 * Until now nothing connected the two: categories linked to events
 * (`category_event`) and to nothing else, so "the pros in this category" was
 * answered by a LIKE of the whole category name against the pro's free-text
 * headline/bio/skills. That never matched — a photographer writes
 * "Fine-Art Wedding Photographer", the category is called "Photography
 * Services" — which is why every category landing page showed zero pros and
 * /browse?q=<category> came back empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // A pro lists a category once; both directions are queried (pros in
            // a category, categories of a pro) so both get an index.
            $table->unique(['category_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_user');
    }
};
