<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Agreement Workspace document, section 7, headed "Critical rule":
 *
 *   "material changes must invalidate prior approvals/signatures for the
 *    changed agreement version... A signature must never silently carry
 *    forward to terms the person did not sign."
 *
 * An agreement already recorded what was agreed and who signed it. What it
 * could not record is WHICH agreement they signed. Without that, changing the
 * price after a signature leaves the signature sitting under the new price,
 * which is the one thing the document says must never happen.
 *
 * So the terms carry a version. Each side's approval records the version it
 * was given, and a signature records the version it was put to. Changing a
 * material term raises the version, and every approval and signature made
 * against the old one stops counting, because it was agreement to something
 * else.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finalizations', function (Blueprint $table) {
            $table->unsignedInteger('terms_version')->default(1)->after('status');
            $table->unsignedInteger('client_approved_version')->nullable()->after('terms_version');
            $table->unsignedInteger('supplier_approved_version')->nullable()->after('client_approved_version');
            $table->unsignedInteger('client_signed_version')->nullable()->after('client_signed_at');
            $table->unsignedInteger('supplier_signed_version')->nullable()->after('supplier_signed_at');
            // Why the agreement went back to being negotiated, in the words of
            // whoever sent it back. Shown to the other side, who has to decide.
            $table->text('change_request')->nullable()->after('supplier_signed_version');
            $table->foreignId('change_requested_by')->nullable()->after('change_request')->constrained('users')->nullOnDelete();
            $table->timestamp('change_requested_at')->nullable()->after('change_requested_by');
            $table->timestamp('declined_at')->nullable()->after('change_requested_at');
            $table->foreignId('declined_by')->nullable()->after('declined_at')->constrained('users')->nullOnDelete();
        });

        /*
         * Anything already signed was signed against the terms as they stand,
         * which is version 1. Backfilling it as null would quietly invalidate
         * signatures that are perfectly good.
         */
        \Illuminate\Support\Facades\DB::table('finalizations')
            ->whereNotNull('client_signed_at')
            ->update(['client_signed_version' => 1, 'client_approved_version' => 1]);

        \Illuminate\Support\Facades\DB::table('finalizations')
            ->whereNotNull('supplier_signed_at')
            ->update(['supplier_signed_version' => 1, 'supplier_approved_version' => 1]);
    }

    public function down(): void
    {
        Schema::table('finalizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('change_requested_by');
            $table->dropConstrainedForeignId('declined_by');
            $table->dropColumn([
                'terms_version', 'client_approved_version', 'supplier_approved_version',
                'client_signed_version', 'supplier_signed_version',
                'change_request', 'change_requested_at', 'declined_at',
            ]);
        });
    }
};
