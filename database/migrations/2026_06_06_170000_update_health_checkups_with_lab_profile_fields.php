<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
            if (! Schema::hasColumn('health_checkups', 'haemoglobin')) {
                $table->decimal('haemoglobin', 5, 2)->nullable()->after('blood_pressure');
            }

            if (! Schema::hasColumn('health_checkups', 'albumin_globulin_ratio')) {
                $table->decimal('albumin_globulin_ratio', 4, 2)->nullable()->after('hba1c');
            }

            if (! Schema::hasColumn('health_checkups', 'alkaline_phosphatase')) {
                $table->decimal('alkaline_phosphatase', 8, 2)->nullable()->after('albumin_globulin_ratio');
            }

            if (! Schema::hasColumn('health_checkups', 'aspartate_transaminase')) {
                $table->decimal('aspartate_transaminase', 8, 2)->nullable()->after('alkaline_phosphatase');
            }

            if (! Schema::hasColumn('health_checkups', 'alanine_transaminase')) {
                $table->decimal('alanine_transaminase', 8, 2)->nullable()->after('aspartate_transaminase');
            }

            if (! Schema::hasColumn('health_checkups', 'gamma_glutamyl_transferase')) {
                $table->decimal('gamma_glutamyl_transferase', 8, 2)->nullable()->after('alanine_transaminase');
            }

            if (! Schema::hasColumn('health_checkups', 'sodium')) {
                $table->decimal('sodium', 6, 2)->nullable()->after('gamma_glutamyl_transferase');
            }

            if (! Schema::hasColumn('health_checkups', 'renal_glucose')) {
                $table->decimal('renal_glucose', 5, 2)->nullable()->after('sodium');
            }
        });

        Schema::table('health_checkups', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('health_checkups', 'spo2') ? 'spo2' : null,
                Schema::hasColumn('health_checkups', 'weight') ? 'weight' : null,
                Schema::hasColumn('health_checkups', 'height') ? 'height' : null,
                Schema::hasColumn('health_checkups', 'bmi') ? 'bmi' : null,
                Schema::hasColumn('health_checkups', 'triglycerides') ? 'triglycerides' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
            if (! Schema::hasColumn('health_checkups', 'spo2')) {
                $table->unsignedTinyInteger('spo2')->nullable()->after('heart_rate');
            }

            if (! Schema::hasColumn('health_checkups', 'weight')) {
                $table->decimal('weight', 5, 1)->nullable()->after('spo2');
            }

            if (! Schema::hasColumn('health_checkups', 'height')) {
                $table->decimal('height', 5, 1)->nullable()->after('weight');
            }

            if (! Schema::hasColumn('health_checkups', 'bmi')) {
                $table->decimal('bmi', 5, 2)->nullable()->after('height');
            }

            if (! Schema::hasColumn('health_checkups', 'triglycerides')) {
                $table->decimal('triglycerides', 5, 2)->nullable()->after('hdl');
            }
        });

        Schema::table('health_checkups', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('health_checkups', 'haemoglobin') ? 'haemoglobin' : null,
                Schema::hasColumn('health_checkups', 'albumin_globulin_ratio') ? 'albumin_globulin_ratio' : null,
                Schema::hasColumn('health_checkups', 'alkaline_phosphatase') ? 'alkaline_phosphatase' : null,
                Schema::hasColumn('health_checkups', 'aspartate_transaminase') ? 'aspartate_transaminase' : null,
                Schema::hasColumn('health_checkups', 'alanine_transaminase') ? 'alanine_transaminase' : null,
                Schema::hasColumn('health_checkups', 'gamma_glutamyl_transferase') ? 'gamma_glutamyl_transferase' : null,
                Schema::hasColumn('health_checkups', 'sodium') ? 'sodium' : null,
                Schema::hasColumn('health_checkups', 'renal_glucose') ? 'renal_glucose' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
