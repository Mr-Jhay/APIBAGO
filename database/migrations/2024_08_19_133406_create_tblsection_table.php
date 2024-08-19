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
        Schema::create('tblsection', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('strand_id');
            $table->string('section');
            $table->timestamps();

            // Adding index on foreign key for better performance
            $table->foreign('strand_id')->references('id')->on('tblstrand')->onDelete('cascade');
           // $table->index('strand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblsection');
    }
};

