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
        Schema::create('user_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // To track which user provided the feedback
            $table->unsignedBigInteger('feedback_id'); // Link to the feedback question
            $table->tinyInteger('rating')->comment('Rating between 1 and 5');
            $table->text('comment')->nullable()->comment('User comment or recommendation');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('feedback_id')->references('id')->on('tblfeedback')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedback');
    }
};

