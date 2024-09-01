<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('joinclass', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('class_id');  // Add class_id here
            $table->tinyInteger('status')->default(0); // 0 = Pending, 1 = Approved
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('tblclass')->onDelete('cascade');
        });
    }

    public function down(): void
    { 
        Schema::dropIfExists('joinclass');
    }
};
