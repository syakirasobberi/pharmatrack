<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDPA: record of the patient's consent to face recognition.
 * face_consent_at NULL with a face_descriptor = face registered before consent was recorded.
 * The history (given / withdrawn) is in the audit log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->timestamp('face_consent_at')->nullable()->after('face_descriptor');
            $table->unsignedBigInteger('face_consent_by')->nullable()->after('face_consent_at'); // staff user who recorded it
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['face_consent_at', 'face_consent_by']);
        });
    }
};
