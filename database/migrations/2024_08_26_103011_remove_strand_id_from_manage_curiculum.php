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
            $table->dropColumn('strand_id');
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
            $table->integer('strand_id')->nullable(); // Adjust the type and options as needed
        });
    }
}
