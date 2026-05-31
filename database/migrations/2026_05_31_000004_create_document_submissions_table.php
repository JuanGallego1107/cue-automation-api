<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('periods')->nullOnDelete();
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('file_path', 500);
            $table->integer('file_size_bytes');
            $table->string('mime_type', 100);
            $table->char('file_hash', 64);
            $table->enum('status', [
                'pending',
                'processing',
                'pending_approval',
                'approved',
                'issues_found',
                'failed',
            ])->default('pending');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->tinyInteger('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite index to optimize "has active review?" queries
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_submissions');
    }
};
