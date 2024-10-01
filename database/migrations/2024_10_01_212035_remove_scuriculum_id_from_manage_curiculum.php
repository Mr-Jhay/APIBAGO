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
                        // First drop the foreign key constraint
                        $table->dropForeign(['scuriculum_id']);
                        // Then drop the column
                        $table->dropColumn('scuriculum_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manage_curiculum', function (Blueprint $table) {
            $table->unsignedBigInteger('scuriculum_id');
            // Re-add the foreign key constraint
            $table->foreign('scuriculum_id')->references('id')->on('strandcuriculum')->onDelete('cascade');
        });
    }
};
