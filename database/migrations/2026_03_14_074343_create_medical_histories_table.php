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
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            // Sambungan ke pesakit
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            
            // Kolum berdasarkan wireframe awak
            $table->string('hypertension')->default('None'); // Cth: Monitored, None
            $table->string('diabetes')->default('None');     // Cth: Type 2 (Controlled), None
            $table->string('allergies')->nullable();         // Cth: Cat, Dust
            $table->string('drug_allergies')->nullable();    // Cth: No known allergies
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
