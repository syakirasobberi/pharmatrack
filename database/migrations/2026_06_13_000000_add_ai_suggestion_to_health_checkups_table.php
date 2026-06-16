<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
            if (! Schema::hasColumn('health_checkups', 'ai_suggestion')) {
                $column = $table->text('ai_suggestion')->nullable();

                if (Schema::hasColumn('health_checkups', 'notes')) {
                    $column->after('notes');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('health_checkups', function (Blueprint $table) {
            if (Schema::hasColumn('health_checkups', 'ai_suggestion')) {
                $table->dropColumn('ai_suggestion');
            }
        });
    }
};
