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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            // Sambungan ke pesakit
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            
            // Kolum ubat-ubatan
            $table->string('name');      // Cth: Metformin
            $table->string('dosage');    // Cth: 500mg
            $table->string('frequency'); // Cth: Twice daily
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
