<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSemesterToTblclass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblclass', function (Blueprint $table) {
            $table->string('semester')->after('year_id'); // Adjust as needed
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
            $table->dropColumn('semester');
        });
    }
}
