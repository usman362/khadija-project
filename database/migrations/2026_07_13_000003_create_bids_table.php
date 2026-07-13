<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->text('note')->nullable();
            // Sealed by default — hidden from other bidders. The bidder can opt in
            // to make the amount publicly visible.
            $table->boolean('is_public')->default(false);
            $table->string('status')->default('submitted'); // submitted|shortlisted|won|declined|withdrawn
            $table->timestamps();
            $table->unique(['event_id', 'supplier_id']); // one bid per pro per gig
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
