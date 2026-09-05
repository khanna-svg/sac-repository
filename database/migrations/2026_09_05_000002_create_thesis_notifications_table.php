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
        if (!Schema::hasTable('thesis_notifications')) {
            Schema::create('thesis_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('user_email');
                $table->string('title');
                $table->text('message');
                $table->string('type')->default('info'); // approved, resubmit, info
                $table->foreignId('document_id')->nullable()->constrained('documents')->onDelete('cascade');
                $table->boolean('is_read')->default(false);
                $table->timestamps();

                $table->index(['user_email', 'is_read']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thesis_notifications');
    }
};
