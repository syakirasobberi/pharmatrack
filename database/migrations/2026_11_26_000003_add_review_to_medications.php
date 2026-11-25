<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pharmacist review of medications. Medicines a patient records in their portal start
 * unreviewed and show on the pharmacist dashboard (with any safety warnings) until a
 * pharmacist reviews, edits or stops them. Existing records count as reviewed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('refill_requested_at');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
        });

        DB::table('medications')->update(['reviewed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->dropColumn(['reviewed_at', 'reviewed_by']);
        });
    }
};
