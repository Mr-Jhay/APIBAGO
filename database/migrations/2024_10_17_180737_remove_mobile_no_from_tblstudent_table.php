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
            $table->dropColumn('Mobile_no'); // This will drop the Mobile_no column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblstudent', function (Blueprint $table) {
            $table->string('Mobile_no')->nullable(); // This will add the Mobile_no column back
        });
    }
};
