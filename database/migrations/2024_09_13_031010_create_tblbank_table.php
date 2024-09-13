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
        Schema::create('tblbank', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Foreign key to users table
            $table->unsignedBigInteger('subject_id'); // Foreign key to exams table
            $table->unsignedBigInteger('question_id'); // Foreign key to users table
            $table->unsignedBigInteger('choice_id'); // Foreign key to exams table
            $table->unsignedBigInteger('correct_id'); 
            $table->string('Quarter');



            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('tblsubject')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('tblquestion')->onDelete('cascade');
            $table->foreign('choice_id')->references('id')->on('addchoices')->onDelete('cascade');
            $table->foreign('correct_id')->references('id')->on('correctanswer')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblbank');
    }
};
