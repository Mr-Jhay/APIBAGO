<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblclass', function (Blueprint $table) {


            $table->id(); // Primary key column
            $table->unsignedBigInteger('user_id'); // Foreign key for users
            $table->unsignedBigInteger('strand_id'); // Foreign key for strands
            $table->unsignedBigInteger('section_id'); // Foreign key for sections
            $table->unsignedBigInteger('subject_id'); // Foreign key for subjects
            $table->unsignedBigInteger('year_id'); // Foreign key for years 
            $table->string('semester'); // Semester column
            $table->string('class_desc')->nullable();; // Description of the class
            $table->string('profile_img')->nullable();; // Path to the profile image
            $table->string('gen_code'); // General code for the class
            $table->timestamps(); // created_at and updated_at columns

            // Add foreign key constraints if needed
             $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
             $table->foreign('strand_id')->references('id')->on('tblstrand')->onDelete('cascade');
             $table->foreign('section_id')->references('id')->on('tblsection')->onDelete('cascade');
             $table->foreign('subject_id')->references('id')->on('tblsubject')->onDelete('cascade');
             $table->foreign('year_id')->references('id')->on('tblyear')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblclass');
    }
};
