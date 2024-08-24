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
        Schema::create('manage_curiculum', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scuriculum_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('strand_id');
            $table->string('semester');
            $table->timestamps();

            $table->foreign('scuriculum_id')->references('id')->on('strandcuriculum')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('tblsubject')->onDelete('cascade');
            $table->foreign('strand_id')->references('id')->on('tblstrand')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manage_curiculum');
    }
};
