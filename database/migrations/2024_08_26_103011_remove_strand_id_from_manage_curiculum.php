<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveStrandIdFromManageCuriculum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manage_curiculum', function (Blueprint $table) {
            // Drop the foreign key constraint before dropping the column
            $table->dropForeign(['strand_id']); // Drop the foreign key
            $table->dropColumn('strand_id'); // Drop the column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manage_curiculum', function (Blueprint $table) {
            // Add the strand_id column back with proper type
            $table->unsignedBigInteger('strand_id')->nullable();

            // Re-add the foreign key constraint if it was there originally
            $table->foreign('strand_id')->references('id')->on('tblstrand')->onDelete('cascade');
        });
    }
}
