<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refill tracking:
 * - supply_until: the day the patient's current supply runs out (set when dispensing / refilling).
 * - refill_reminder_sent_for: the supply_until date a reminder was already sent for, so each
 *   supply period gets one reminder and a refill (new date) allows the next one.
 * - refill_requested_at: the patient asked for a refill from their portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->date('supply_until')->nullable()->after('end_date');
            $table->date('refill_reminder_sent_for')->nullable()->after('supply_until');
            $table->timestamp('refill_requested_at')->nullable()->after('refill_reminder_sent_for');
        });
    }

    public function down(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->dropColumn(['supply_until', 'refill_reminder_sent_for', 'refill_requested_at']);
        });
    }
};
