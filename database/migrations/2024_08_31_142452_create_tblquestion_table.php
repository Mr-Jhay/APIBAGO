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
        Schema::create('tblquestion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tblschedule_id');
            $table->string('question_type');;
            $table->text('question');
            $table->foreign('tblschedule_id')->references('id')->on('tblschedule')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblquestion');
    }
};
