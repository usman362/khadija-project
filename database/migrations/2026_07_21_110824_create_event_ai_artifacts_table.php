<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_ai_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who attached it
            $table->string('tool_key', 60);          // e.g. budget-allocator
            $table->string('tool_name');             // human label at save time
            $table->string('title');                 // short summary line
            $table->json('payload')->nullable();     // the tool's structured result
            $table->string('mode', 12)->default('manual'); // manual | auto (by AI level)
            $table->timestamps();
            $table->index(['event_id', 'tool_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_ai_artifacts');
    }
};
