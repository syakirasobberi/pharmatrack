<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('patients', function (Blueprint $table) {
        // Kita guna longText sebab data 128 nombor ini agak panjang bila ditukar ke teks.
        // nullable() sangat penting supaya 2 pesakit sedia ada awak tidak menyebabkan ralat (error).
        $table->longText('face_descriptor')->nullable()->after('bmi'); 
    });
}

public function down()
{
    Schema::table('patients', function (Blueprint $table) {
        $table->dropColumn('face_descriptor');
    });
}
};
