<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')
                ->constrained('document_submissions')
                ->cascadeOnDelete();
            $table->string('job_id', 255)->nullable();
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued');
            $table->tinyInteger('attempts')->default(0);
            $table->json('payload')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_jobs');
    }
};
