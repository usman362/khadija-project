<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'category_id']);
        });

        // Migrate existing category_id data to pivot table
        $events = DB::table('events')->whereNotNull('category_id')->get(['id', 'category_id']);
        foreach ($events as $event) {
            DB::table('category_event')->insert([
                'event_id' => $event->id,
                'category_id' => $event->category_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_event');
    }
};
