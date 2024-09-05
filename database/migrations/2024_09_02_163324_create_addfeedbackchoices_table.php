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
        Schema::create('addfeedbackchoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tblfeedback_id');
            $table->tinyInteger('rate'); // Assuming rate is between 1 and 5
            $table->text('description')->nullable(); 

            $table->timestamps();

            // Foreign key constraint
           // $table->foreign('tblfeedback_id')->references('id')->on('tblfeedback');
            $table->foreign('tblfeedback_id')->references('id')->on('tblfeedback')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addfeedbackchoices');
    }
};
