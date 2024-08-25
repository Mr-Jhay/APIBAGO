<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblclass', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('scuriculum_id'); // Curriculum ID
            $table->unsignedBigInteger('strand_id');  
            $table->unsignedBigInteger('subject_id');
            $table->string('class_desc')->nullable();
            $table->string('profile_img')->nullable(); 
            $table->string('gen_code');
            $table->string('semester'); // Added semester
            $table->string('year');     // Added year

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('scuriculum_id')->references('id')->on('manage_curiculum')->onDelete('cascade'); // Foreign key to manage_curiculum
            $table->foreign('strand_id')->references('id')->on('tblstrand')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('tblsubject')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblclass');
    }
};
