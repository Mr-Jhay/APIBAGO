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
        Schema::table('tblyear', function (Blueprint $table) {
            $table->dropColumn('status');  // Drop the status column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblyear', function (Blueprint $table) {
            $table->tinyint('status')->default(0);  
        });
    }
};
