<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_feature_usages', function (Blueprint $table) {
            $table->unsignedSmallInteger('credits')->default(1)->after('tokens_used');
        });
    }

    public function down(): void
    {
        Schema::table('ai_feature_usages', function (Blueprint $table) {
            $table->dropColumn('credits');
        });
    }
};
