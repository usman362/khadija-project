<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_feature_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature_code', 60);
            $table->unsignedInteger('tokens_used')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'feature_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feature_usages');
    }
};
