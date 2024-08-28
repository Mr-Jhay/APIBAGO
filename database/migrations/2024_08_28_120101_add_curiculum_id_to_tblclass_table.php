<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('tblclass', function (Blueprint $table) {
            $table->unsignedBigInteger('curiculum_id')->after('user_id'); // Add the FK column

            // Set the foreign key constraint
          //  $table->foreign('curiculum_id')->references('scuriculum_id')->on('strandcuriculum')->onDelete('cascade');

            $table->foreign('curiculum_id')->references('id')->on('strandcuriculum')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblclass', function (Blueprint $table) {
            $table->dropForeign(['curiculum_id']); // Drop the FK constraint
            $table->dropColumn('curiculum_id');    // Drop the column
        });
    }
};
