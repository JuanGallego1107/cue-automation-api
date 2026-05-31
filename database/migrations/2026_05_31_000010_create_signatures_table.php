<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')
                ->constrained('document_submissions')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('hash', 255);
            $table->enum('signature_type', ['digital', 'manual_scan', 'none']);
            $table->string('signature_hash', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('signed_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
