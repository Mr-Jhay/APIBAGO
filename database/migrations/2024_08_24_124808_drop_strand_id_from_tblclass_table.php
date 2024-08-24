<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropStrandIdFromTblclassTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblclass', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['strand_id']);
            
            // Drop the column
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
        Schema::table('tblclass', function (Blueprint $table) {
            // Re-add the column
            $table->unsignedBigInteger('strand_id')->nullable();
            
            // Re-add the foreign key constraint if needed
            // Assuming 'strands' is the referenced table and 'id' is the primary key
            $table->foreign('strand_id')->references('id')->on('tblstrand')->onDelete('cascade');
        });
    }
}


