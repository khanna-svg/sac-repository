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
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'academic_level')) {
                $table->string('academic_level')->default('Undergraduate')->after('department');
            }
            if (!Schema::hasColumn('documents', 'research_category')) {
                $table->string('research_category')->default('Capstone Project')->after('academic_level');
            }
            if (!Schema::hasColumn('documents', 'methodology')) {
                $table->string('methodology')->nullable()->after('research_category');
            }
            if (!Schema::hasColumn('documents', 'keywords')) {
                $table->text('keywords')->nullable()->after('methodology');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['academic_level', 'research_category', 'methodology', 'keywords']);
        });
    }
};
