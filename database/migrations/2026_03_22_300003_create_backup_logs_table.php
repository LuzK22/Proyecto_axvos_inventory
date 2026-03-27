<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->default('');
            $table->bigInteger('size_bytes')->default(0);
            $table->enum('type', ['manual', 'scheduled'])->default('manual');
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->string('disk', 50)->default('local');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->foreignId('downloaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
