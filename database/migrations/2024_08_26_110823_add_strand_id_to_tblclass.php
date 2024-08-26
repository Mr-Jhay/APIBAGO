<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStrandIdToTblclass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblclass', function (Blueprint $table) {
            $table->unsignedBigInteger('strand_id')->after('user_id'); // Replace 'existing_column' with the column after which you want to add 'strand_id'

            $table->foreign('strand_id')->references('id')->on('tblstrand')->onDelete('cascade'); // Adjust 'tblstrand' to your strands table name
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
            $table->dropForeign(['strand_id']);
            $table->dropColumn('strand_id');
        });
    }
}
