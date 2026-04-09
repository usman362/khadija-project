<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deletion_requested_at')->nullable()->after('referral_attributed_at');
            $table->timestamp('deletion_scheduled_at')->nullable()->after('deletion_requested_at');
            $table->text('deletion_reason')->nullable()->after('deletion_scheduled_at');
            $table->softDeletes()->after('updated_at');

            $table->index('deletion_scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['deletion_scheduled_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['deletion_requested_at', 'deletion_scheduled_at', 'deletion_reason']);
        });
    }
};
