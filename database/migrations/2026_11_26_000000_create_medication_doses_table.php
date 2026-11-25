<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dose log for real medication adherence: one row per medication per day it was taken.
 * source = who recorded it (patient portal, pharmacist, kiosk).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_doses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained('medications')->cascadeOnDelete();
            $table->date('taken_on');
            $table->string('source', 20);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['medication_id', 'taken_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_doses');
    }
};
