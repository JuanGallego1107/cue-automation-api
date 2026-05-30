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
        Schema::table('users', function (Blueprint $table) {
            // Nuevas relaciones
            $table->foreignId('role_id')
                ->after('password')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->foreignId('program_id')
                ->nullable()
                ->after('role_id')
                ->constrained('programs')
                ->nullOnDelete();

            // Estado del usuario
            $table->boolean('is_active')
                ->default(true)
                ->after('program_id');

            // Soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['program_id']);

            $table->dropColumn([
                'role_id',
                'program_id',
                'is_active',
                'deleted_at',
            ]);
        });
    }
};