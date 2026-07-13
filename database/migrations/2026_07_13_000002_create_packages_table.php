<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('solo');        // solo | co-op
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('price_unit')->default('flat');   // flat | from | hourly
            $table->string('duration')->nullable();          // e.g. "6 hours"
            $table->json('includes')->nullable();            // array of what's included
            $table->json('images')->nullable();              // pipeline image size sets
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'is_active']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
