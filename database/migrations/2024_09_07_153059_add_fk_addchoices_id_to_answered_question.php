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
            // Add the addchoices_id column
            $table->unsignedBigInteger('addchoices_id')->nullable()->after('tblquestion_id');
            
            // Add the Student_answer column
            $table->text('Student_answer')->nullable()->after('addchoices_id');

            // Add the foreign key constraint for the addchoices_id column
            $table->foreign('addchoices_id')->references('id')->on('addchoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answered_question', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['addchoices_id']);
            
            // Drop the addchoices_id column
            $table->dropColumn('addchoices_id');
            
            // Drop the Student_answer column if it was added
            $table->dropColumn('Student_answer');
        });
    }
};
