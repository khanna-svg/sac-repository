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
            if (!Schema::hasColumn('documents', 'status')) {
                $table->string('status')->default('approved')->after('course_code');
            }
            if (!Schema::hasColumn('documents', 'submitted_by_name')) {
                $table->string('submitted_by_name')->nullable()->after('status');
            }
            if (!Schema::hasColumn('documents', 'submitted_by_email')) {
                $table->string('submitted_by_email')->nullable()->after('submitted_by_name');
            }
            if (!Schema::hasColumn('documents', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('submitted_by_email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['status', 'submitted_by_name', 'submitted_by_email', 'admin_notes']);
        });
    }
};
