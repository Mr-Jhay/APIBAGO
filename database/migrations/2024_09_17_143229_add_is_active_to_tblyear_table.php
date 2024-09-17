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
        Schema::table('tblyear', function (Blueprint $table) {
            // Add the 'is_active' column with a default value of false
            $table->boolean('is_active')->default(false)->after('addyear'); // Add after 'addyear' for better ordering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblyear', function (Blueprint $table) {
            // Drop the 'is_active' column if the migration is rolled back
            $table->dropColumn('is_active');
        });
    }
};
