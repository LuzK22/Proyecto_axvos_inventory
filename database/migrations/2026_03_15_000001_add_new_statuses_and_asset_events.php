<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Nuevos estados ────────────────────────────────────────────
        $newStatuses = [
            ['name' => 'En Traslado',   'color' => 'info'],
            ['name' => 'Donado',        'color' => 'dark'],
            ['name' => 'Vendido',       'color' => 'dark'],
        ];

        foreach ($newStatuses as $s) {
            DB::table('statuses')->insertOrIgnore([
                'name'       => $s['name'],
                'color'      => $s['color'],
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── 2. Tabla asset_events (historial de transiciones) ────────────
        Schema::create('asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();

            // Estado anterior y nuevo
            $table->string('from_status')->nullable();   // nombre del estado anterior
            $table->string('to_status');                 // nombre del nuevo estado

            // Tipo de evento
            $table->string('event_type');
            // asignacion | devolucion | baja | mantenimiento | garantia
            // traslado   | donacion   | venta | actualizacion | disponible

            // Referencias opcionales
            $table->foreignId('assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('acta_id')->nullable()->constrained('actas')->nullOnDelete();

            // Sucursal destino (para traslados)
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->nullOnDelete();

            // Usuario que registra el evento
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Colaborador involucrado (asignación/devolución)
            $table->foreignId('collaborator_id')->nullable()->constrained()->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_events');

        DB::table('statuses')
            ->whereIn('name', ['En Traslado', 'Donado', 'Vendido'])
            ->delete();
    }
};
