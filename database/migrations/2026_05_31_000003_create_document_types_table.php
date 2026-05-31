<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->json('allowed_extensions');
            $table->tinyInteger('max_size_mb');
            $table->string('naming_pattern', 255)->nullable();
            $table->tinyInteger('requires_signature')->default(0);
            $table->json('validation_rules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
