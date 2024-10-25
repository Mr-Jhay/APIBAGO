<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('manage_curiculum', function (Blueprint $table) {
            // Check if the column exists before trying to drop the foreign key
            if (Schema::hasColumn('manage_curiculum', 'scuriculum_id')) {
                // Check if the foreign key exists
                $foreignKeyExists = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'manage_curiculum' 
                    AND COLUMN_NAME = 'scuriculum_id'
                    AND CONSTRAINT_SCHEMA = DATABASE()
                ");

                // If the foreign key exists, drop it
                if ($foreignKeyExists) {
                    $table->dropForeign(['scuriculum_id']);
                }

                // Then drop the column itself
                $table->dropColumn('scuriculum_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manage_curiculum', function (Blueprint $table) {
            // Re-add the column
            $table->unsignedBigInteger('scuriculum_id');
            // Re-add the foreign key constraint
            $table->foreign('scuriculum_id')->references('id')->on('strandcuriculum')->onDelete('cascade');
        });
    }
};
