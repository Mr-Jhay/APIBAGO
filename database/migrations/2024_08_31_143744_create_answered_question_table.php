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
        Schema::create('answered_question', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('users_id');
            $table->unsignedBigInteger('tblquestion_id');
            $table->unsignedBigInteger('correctanswer_id')->nullable();
            $table->foreign('users_id')->references('id')->on('users');
            $table->foreign('tblquestion_id')->references('id')->on('tblquestion');
            $table->foreign('correctanswer_id')->references('id')->on('correctanswer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answered_question');
    }
};
