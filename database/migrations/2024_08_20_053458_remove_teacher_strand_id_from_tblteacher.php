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
        Schema::table('tblteacher', function (Blueprint $table) {
            if (Schema::hasColumn('tblteacher', 'teacher_strand_id')) {
                $table->dropColumn('teacher_strand_id');
            }
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblteacher', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_strand_id')->nullable()->after('updated_at');//
        });
    }
};
