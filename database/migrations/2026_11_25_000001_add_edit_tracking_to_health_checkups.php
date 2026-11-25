<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who last corrected a check-up and when, shown in the app.
 * Separate from updated_at, which also changes when the AI suggestion is regenerated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
            $table->foreignId('edited_by')->nullable()->after('pharmacist_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('edited_at')->nullable()->after('edited_by');
        });
    }

    public function down(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edited_by');
            $table->dropColumn('edited_at');
        });
    }
};
