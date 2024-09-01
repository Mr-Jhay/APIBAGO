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
        Schema::create('tblschedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classtable_id');
            $table->string('title');
            $table->string('quarter');
            $table->timestamp('start')->nullable();
            $table->timestamp('end')->nullable();
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('classtable_id')
                  ->references('id')
                  ->on('tblclass')
                  ->onDelete('cascade');

            // Add index to foreign key column
            $table->index('classtable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblschedule', function (Blueprint $table) {
            // Drop foreign key and index
            $table->dropForeign(['classtable_id']);
            $table->dropIndex(['classtable_id']);
        });

        Schema::dropIfExists('tblschedule');
    }
};
