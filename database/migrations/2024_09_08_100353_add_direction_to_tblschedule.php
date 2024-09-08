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
            $table->text('Direction')->nullable()->after('end'); // Add the new column as TEXT
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblschedule', function (Blueprint $table) {
            $table->dropColumn('Direction'); // Drop the column if rolling back
        });
    }
};
