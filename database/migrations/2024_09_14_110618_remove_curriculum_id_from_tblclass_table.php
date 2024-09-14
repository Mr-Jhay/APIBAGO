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
        Schema::table('tblclass', function (Blueprint $table) {
            $table->dropForeign(['curiculum_id']);
            
            // Then drop the curriculum_id column
            $table->dropColumn('curiculum_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblclass', function (Blueprint $table) {
            $table->unsignedBigInteger('curriculum_id');

            // Restore the foreign key constraint
            $table->foreign('curriculum_id')->references('id')->on('curriculums')->onDelete('cascade');
        });
    }
};
