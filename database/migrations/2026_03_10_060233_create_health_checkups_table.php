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
        Schema::create('health_checkups', function (Blueprint $table) {
            $table->id();
            // Hubungan dengan pesakit (Jika pesakit dipadam, rekod ini pun terpadam)
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            // Hubungan dengan ahli farmasi yang merekod
            $table->foreignId('pharmacist_id')->constrained('users')->onDelete('cascade');
            
            // Data pemeriksaan
            $table->string('blood_pressure')->nullable(); // cth: 120/80
            $table->decimal('blood_sugar', 5, 2)->nullable();
            $table->decimal('cholesterol', 5, 2)->nullable();
            $table->date('checkup_date');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_checkups');
    }
};
