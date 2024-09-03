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
        Schema::create('save_the_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('users_id'); // Student's ID
            $table->unsignedBigInteger('tblfeedback_id'); // Feedback ID
         //   $table->unsignedBigInteger('tblreccomendation_id'); // Recommendation ID
            $table->unsignedBigInteger('tblreccomendation_id')->nullable(); // Optional Recommendation ID
            $table->tinyInteger('rate'); // Rating
            $table->text('answer'); // Answer to the feedback question
            $table->timestamps();

            // Foreign key constraints
          //  $table->foreign('users_id')->references('id')->on('users');
          //  $table->foreign('tblfeedback_id')->references('id')->on('tblfeedback');
           // $table->foreign('tblreccomendation_id')->references('id')->on('tblreccomendation');
           // $table->foreign('tblreccomendation2_id')->references('id')->on('tblreccomendation')->nullable();

            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tblfeedback_id')->references('id')->on('tblfeedback')->onDelete('cascade');
            $table->foreign('tblreccomendation_id')->references('id')->on('tblreccomendation_id')->onDelete('cascade');
           // $table->foreign('tblreccomendation_id2')->references('id')->on('tblreccomendation_id2')->onDelete('cascade')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('save_the_data');
    }
};
