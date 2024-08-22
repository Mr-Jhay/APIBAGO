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
            // Add the new column after the 'id' column
            $table->unsignedBigInteger('scuriculum_id')->after('id');

            // Add the foreign key constraint
            $table->foreign('scuriculum_id')->references('id')->on('strandcuriculum')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manage_curiculum', function (Blueprint $table) {
            // Drop the foreign key first, then the column
            $table->dropForeign(['scuriculum_id']);
            $table->dropColumn('scuriculum_id');
        });
    }
};

