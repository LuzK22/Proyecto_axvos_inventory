<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            // FK CORRECTA
            $table->foreignId('asset_type_id')
                ->constrained('asset_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('internal_code')->unique(); // TI-LAP-001
            $table->string('asset_tag')->nullable()->unique();

            $table->string('brand');
            $table->string('model');
            $table->string('serial')->unique();

            $table->enum('property_type', [
                'PROPIO',
                'LEASING',
                'ALQUILADO',
                'OTRO'
            ]);

            $table->foreignId('status_id')
                ->constrained('statuses');

            $table->foreignId('branch_id')
                ->constrained('branches');

            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
