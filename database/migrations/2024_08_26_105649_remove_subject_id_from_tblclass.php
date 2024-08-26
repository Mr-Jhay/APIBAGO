
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveSubjectIdFromTblclass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblclass', function (Blueprint $table) {
            // Replace 'tblclass_subject_id_foreign' with the actual constraint name
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
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
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreign('subject_id')->references('id')->on('subjects'); // Adjust as necessary
        });
    }
}

