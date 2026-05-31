<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')
                ->constrained('document_submissions')
                ->cascadeOnDelete();
            $table->string('model_used', 100);
            $table->enum('analysis_type', ['validation', 'extraction', 'classification']);
            $table->enum('result', ['pass', 'fail', 'warning', 'error']);
            $table->text('summary')->nullable();
            $table->json('findings')->nullable();
            $table->json('anomalies')->nullable();
            $table->text('recommendations')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->integer('tokens_used')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
