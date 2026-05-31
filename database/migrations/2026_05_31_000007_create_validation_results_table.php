<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')
                ->constrained('document_submissions')
                ->cascadeOnDelete();
            $table->tinyInteger('passed');
            $table->decimal('score', 5, 2)->nullable();
            $table->json('checks_performed');
            $table->json('checks_passed');
            $table->json('checks_failed');
            $table->json('inconsistencies')->nullable();
            $table->text('recommendations')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_results');
    }
};
