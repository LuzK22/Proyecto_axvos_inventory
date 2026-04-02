<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subcategories')) {
            return;
        }

        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->enum('category', ['TI', 'OTRO']);
            $table->timestamps();

            $table->unique(['name', 'category']);
        });

        // Seed from existing asset_types subcategory values
        $existing = DB::table('asset_types')
            ->select('subcategory', 'category')
            ->whereNotNull('subcategory')
            ->distinct()
            ->get();

        foreach ($existing as $row) {
            DB::table('subcategories')->insertOrIgnore([
                'name'       => $row->subcategory,
                'category'   => $row->category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategories');
    }
};
