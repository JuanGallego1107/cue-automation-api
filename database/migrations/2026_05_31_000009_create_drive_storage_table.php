<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_storage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')
                ->constrained('document_submissions')
                ->cascadeOnDelete();
            $table->string('drive_file_id', 255);
            $table->string('drive_folder_id', 255);
            $table->string('drive_url', 500);
            $table->string('drive_filename', 255);
            $table->string('folder_path', 500);
            $table->integer('file_size_bytes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_storage');
    }
};
