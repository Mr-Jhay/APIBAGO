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
            $table->tinyInteger('status')->default(0);//meaning active
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblyear', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};