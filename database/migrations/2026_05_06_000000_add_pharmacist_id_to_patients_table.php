<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('pharmacist_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        $firstPharmacistId = DB::table('users')
            ->where('role', 'pharmacist')
            ->orderBy('id')
            ->value('id');

        if ($firstPharmacistId) {
            DB::table('patients')
                ->whereNull('pharmacist_id')
                ->update(['pharmacist_id' => $firstPharmacistId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pharmacist_id');
        });
    }
};
