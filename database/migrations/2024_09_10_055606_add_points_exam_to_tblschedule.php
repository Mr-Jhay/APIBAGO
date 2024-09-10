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
        Schema::table('tblschedule', function (Blueprint $table) {
            $table->smallInteger('points_exam')->nullable()->after('end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblschedule', function (Blueprint $table) {
            $table->dropColumn('points_exam');
        });
    }
};
