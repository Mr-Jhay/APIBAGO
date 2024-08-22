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
        Schema::create('tblclass', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('strand_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('subject_id');
            $table->string('class_desc')->nullable();
            $table->string('profile_img')->nullable(); // Corrected this line
            $table->string('gen_code');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('strand_id')->references('id')->on('tblstrand')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('tblsection')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('manage_curiculum')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblclass');
    }
};
