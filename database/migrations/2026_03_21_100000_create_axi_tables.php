<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('axi_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Nueva conversación');
            $table->timestamps();
        });

        Schema::create('axi_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                  ->constrained('axi_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant']);
            $table->longText('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('axi_messages');
        Schema::dropIfExists('axi_conversations');
    }
};
