<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            if (!Schema::hasColumn('medications', 'notes')) {
                $table->string('notes')->nullable()->after('dosage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            if (Schema::hasColumn('medications', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
