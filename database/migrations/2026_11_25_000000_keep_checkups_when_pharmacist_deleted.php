<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting a pharmacist's account used to cascade-delete every check-up they recorded.
 * Keep the patient's check-ups and just clear the pharmacist link instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
            $table->dropForeign(['pharmacist_id']);
            $table->unsignedBigInteger('pharmacist_id')->nullable()->change();
            $table->foreign('pharmacist_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
            $table->dropForeign(['pharmacist_id']);
            $table->unsignedBigInteger('pharmacist_id')->nullable(false)->change();
            $table->foreign('pharmacist_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
