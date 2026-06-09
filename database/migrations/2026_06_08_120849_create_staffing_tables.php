<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Team & Staffing subsystem — a professional's crew (staff_members) and the
 * shifts they work / open shifts that need filling.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete(); // owning professional
            $table->string('name');
            $table->string('role')->nullable();           // Server, Bartender, DJ…
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('status')->default('active');  // active | inactive
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff_members')->nullOnDelete(); // null = open shift
            $table->string('role');                       // Server (Evening), Bartender…
            $table->string('location')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('slots')->default(1); // e.g. "Setup Crew (2)"
            $table->string('status')->default('open');    // open | assigned | on_shift | completed | cancelled
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('staff_members');
    }
};
