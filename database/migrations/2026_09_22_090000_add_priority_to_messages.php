<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How urgent a message is (Sir Peter, 21 Sep): Routine, Important, Priority,
 * Urgent, Critical. Set by either person in the conversation, and shown in
 * one place only, on the conversation's row in the list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('priority', 12)->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
