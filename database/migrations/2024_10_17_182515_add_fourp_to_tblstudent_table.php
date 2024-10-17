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
        Schema::table('tblstudent', function (Blueprint $table) {
            $table->boolean('fourp')->default(false)->after('section_id'); // Adds a boolean column fourp with a default value of false
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblstudent', function (Blueprint $table) {
            $table->dropColumn('fourp'); // Drops the fourp column
        });
    }
};
