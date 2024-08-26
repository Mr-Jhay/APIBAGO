<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStrandIdToManageCurriculumTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manage_curiculum', function (Blueprint $table) {
            // Add the new column after 'scuriculum'
            $table->unsignedBigInteger('strand_id')->after('scuriculum_id');
            
            // Add foreign key constraint
            $table->foreign('strand_id')->references('id')->on('tblstrand')->onDelete('cascade');
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
            // Drop foreign key constraint
            $table->dropForeign(['strand_id']);
            
            // Drop the column
            $table->dropColumn('strand_id');
        });
    }
}

