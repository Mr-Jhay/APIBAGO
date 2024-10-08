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
        Schema::table('correctanswer', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('correct_answer');
        });
    }

    /**
     * Reverse the migrations.
     */ 
    public function down(): void
    {
        Schema::table('correctanswer', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
};
