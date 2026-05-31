<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')
                ->unique()
                ->constrained('document_submissions')
                ->cascadeOnDelete();
            $table->enum('engine', ['azure_document_intelligence', 'google_document_ai', 'manual']);
            $table->longText('extracted_text')->nullable();
            $table->json('structured_data')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->integer('pages_processed')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_results');
    }
};
