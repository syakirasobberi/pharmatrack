<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
 
            // Meta
            $table->string('report_source')->nullable()->after('checkup_date');
 
            // Vitals
            $table->unsignedSmallInteger('heart_rate')->nullable()->after('blood_pressure');
            $table->unsignedTinyInteger('spo2')->nullable()->after('heart_rate');
            $table->decimal('weight', 5, 1)->nullable()->after('spo2');
            $table->decimal('height', 5, 1)->nullable()->after('weight');
            $table->decimal('bmi', 5, 2)->nullable()->after('height');
 
            // Blood glucose
            $table->decimal('hba1c', 4, 1)->nullable()->after('blood_sugar');
 
            // Lipid panel
            $table->decimal('ldl', 5, 2)->nullable()->after('cholesterol');
            $table->decimal('hdl', 5, 2)->nullable()->after('ldl');
            $table->decimal('triglycerides', 5, 2)->nullable()->after('hdl');
 
            // Notes
            $table->text('notes')->nullable()->after('triglycerides');
 
            // Soft delete
            $table->softDeletes()->after('notes');
        });
    }
 
    /**
     * Reverse the migrations.
     * Removes all added columns if you rollback.
     */
    public function down(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
            $table->dropColumn([
                'report_source',
                'heart_rate',
                'spo2',
                'weight',
                'height',
                'bmi',
                'hba1c',
                'ldl',
                'hdl',
                'triglycerides',
                'notes',
                'deleted_at',
            ]);
        });
    }
};
