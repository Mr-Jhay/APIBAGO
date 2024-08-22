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
        Schema::table('manage_curiculum', function (Blueprint $table) {
            // Add the 'status' column with a default value of 1
            $table->tinyInteger('status')->default(1);//meaning active
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manage_curiculum', function (Blueprint $table) {
            // Drop the 'status' column if the migration is rolled back
            $table->dropColumn('status');
        });
    }
};
