<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('last_taken')->nullable();
        });
    }

    public function down()
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'last_taken']);
        });
    }
};