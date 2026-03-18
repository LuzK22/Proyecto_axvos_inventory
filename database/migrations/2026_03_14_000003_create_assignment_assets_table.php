<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_assets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                  ->constrained('assignments')
                  ->cascadeOnDelete();

            $table->foreignId('asset_id')
                  ->constrained('assets')
                  ->restrictOnDelete();

            $table->timestamp('assigned_at');
            $table->timestamp('returned_at')->nullable();

            $table->text('return_notes')->nullable();

            $table->foreignId('returned_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_assets');
    }
};
