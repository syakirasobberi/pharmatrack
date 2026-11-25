<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - medications.stopped_at / stopped_reason: a pharmacist stops a medication (kept as history).
 * - patients.archived_at: a pharmacist archives a patient (hidden everywhere, records kept).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->timestamp('stopped_at')->nullable()->after('last_taken');
            $table->string('stopped_reason')->nullable()->after('stopped_at');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('pharmacist_id');
        });
    }

    public function down(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->dropColumn(['stopped_at', 'stopped_reason']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
