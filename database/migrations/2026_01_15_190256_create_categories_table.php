<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();      // Nombre de la categoría o subcategoría
            $table->unsignedBigInteger('parent_id')->nullable(); // Para subcategorías
            $table->timestamps();

            // Relación con la misma tabla para subcategorías
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
