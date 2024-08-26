<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubjectIdToTblclass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblclass', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->after('section_id'); // Adjust as needed

            $table->foreign('subject_id')->references('id')->on('tblsubject')->onDelete('cascade'); // Adjust 'tblsubjects' to your subjects table name
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
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
        });
    }
}
