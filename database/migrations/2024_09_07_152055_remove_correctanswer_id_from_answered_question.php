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
        Schema::table('answered_question', function (Blueprint $table) {
            // Drop the foreign key constraint (adjust the constraint name if needed)
            $table->dropForeign(['correctanswer_id']);
            
            // Now drop the column
            $table->dropColumn('correctanswer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answered_question', function (Blueprint $table) {
            // Add the column back
            $table->unsignedBigInteger('correctanswer_id')->nullable();

            // Optionally, add the foreign key constraint back if necessary
             $table->foreign('correctanswer_id')->references('id')->on('correctanswer');
        });
    }
};
