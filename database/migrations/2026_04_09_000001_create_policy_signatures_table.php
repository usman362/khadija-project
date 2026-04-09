<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('policy_type', 60); // privacy_policy, ai_usage_agreement, terms_of_service
            $table->string('policy_version', 20)->default('1.0');
            $table->string('signature_type', 20)->default('typed'); // typed | drawn
            $table->text('signature_data');   // typed: full name  |  drawn: base64 PNG
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();

            // One signature per user per policy version
            $table->unique(['user_id', 'policy_type', 'policy_version']);
            $table->index(['policy_type', 'signed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_signatures');
    }
};
