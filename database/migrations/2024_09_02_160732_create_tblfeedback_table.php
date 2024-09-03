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
        Schema::create('tblfeedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->text('question');
            $table->timestamps();

            // Foreign key constraint
           // $table->foreign('class_id')->references('id')->on('classes');
            $table->foreign('class_id')->references('id')->on('tblclass')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblfeedback');
    }
};
