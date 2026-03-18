<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssignmentType;
use App\Models\AssignmentTemplate;
use App\Models\AssetType;

/**
 * Plantillas de asignación predeterminadas para AXVOS.
 *
 * Cada empresa configura sus propias plantillas desde
 * Admin → Configuración → Plantillas de Asignación.
 *
 * Este seeder instala las plantillas base de AXVOS basadas
 * en modalidad de trabajo (Remoto / Presencial / Mixto).
 *
 * Otras empresas pueden:
 *   - Desactivar estas plantillas
 *   - Crear las suyas (por cargo, área, proyecto, etc.)
 *   - No usar plantillas (asignación libre)
 */
class AssignmentTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // ── Tipo: Por Modalidad de Trabajo (AXVOS) ───────────────────────
        $type = AssignmentType::firstOrCreate(
            ['name' => 'Por Modalidad de Trabajo'],
            [
                'trigger_field' => 'modalidad',
                'trigger_label' => 'Modalidad de Trabajo',
                'target'        => 'person',
                'requires_return' => true,
                'active'        => true,
                'sort_order'    => 1,
            ]
        );

        // IDs de tipos de activo TI
        $tipos = AssetType::where('category', 'TI')
            ->whereIn('name', ['Portátil', 'Monitor', 'Mouse', 'Teclado', 'Diadema', 'Cargador'])
            ->pluck('id', 'name');

        // ── Plantilla: Trabajo Remoto ─────────────────────────────────────
        $remoto = AssignmentTemplate::firstOrCreate(
            ['assignment_type_id' => $type->id, 'trigger_value' => 'remoto'],
            [
                'name'        => 'Trabajo Remoto',
                'description' => 'El colaborador se lleva el equipo a casa. Monitor, teclado y mouse quedan en el puesto asignado al jefe o área.',
                'active'      => true,
                'sort_order'  => 1,
            ]
        );

        $remoto->items()->delete();
        $this->createItems($remoto->id, [
            // Va con el colaborador
            ['name' => 'Portátil',  'goes_to' => 'assignee', 'qty' => 1, 'notes' => 'Equipo principal del colaborador'],
            ['name' => 'Diadema',   'goes_to' => 'assignee', 'qty' => 1, 'notes' => 'Para reuniones virtuales'],
            ['name' => 'Cargador',  'goes_to' => 'assignee', 'qty' => 1, 'notes' => 'Cargador del equipo'],
            // Queda en oficina
            ['name' => 'Monitor',   'goes_to' => 'jefe',     'qty' => 1, 'notes' => 'Queda en puesto asignado al jefe'],
            ['name' => 'Teclado',   'goes_to' => 'jefe',     'qty' => 1, 'notes' => 'Queda en puesto asignado al jefe'],
            ['name' => 'Mouse',     'goes_to' => 'jefe',     'qty' => 1, 'notes' => 'Queda en puesto asignado al jefe'],
        ], $tipos);

        // ── Plantilla: Trabajo Presencial ─────────────────────────────────
        $presencial = AssignmentTemplate::firstOrCreate(
            ['assignment_type_id' => $type->id, 'trigger_value' => 'presencial'],
            [
                'name'        => 'Trabajo Presencial',
                'description' => 'Equipo completo de puesto asignado al colaborador.',
                'active'      => true,
                'sort_order'  => 2,
            ]
        );

        $presencial->items()->delete();
        $this->createItems($presencial->id, [
            ['name' => 'Portátil', 'goes_to' => 'assignee', 'qty' => 1],
            ['name' => 'Monitor',  'goes_to' => 'assignee', 'qty' => 1],
            ['name' => 'Teclado',  'goes_to' => 'assignee', 'qty' => 1],
            ['name' => 'Mouse',    'goes_to' => 'assignee', 'qty' => 1],
            ['name' => 'Diadema',  'goes_to' => 'assignee', 'qty' => 1],
            ['name' => 'Cargador', 'goes_to' => 'assignee', 'qty' => 1],
        ], $tipos);

        // ── Plantilla: Trabajo Mixto ──────────────────────────────────────
        $mixto = AssignmentTemplate::firstOrCreate(
            ['assignment_type_id' => $type->id, 'trigger_value' => 'hibrido'],
            [
                'name'        => 'Trabajo Mixto',
                'description' => 'El colaborador se lleva el portátil. Monitor, teclado y mouse quedan en área compartida de oficina.',
                'active'      => true,
                'sort_order'  => 3,
            ]
        );

        $mixto->items()->delete();
        $this->createItems($mixto->id, [
            // Va con el colaborador
            ['name' => 'Portátil',  'goes_to' => 'assignee', 'qty' => 1],
            ['name' => 'Diadema',   'goes_to' => 'assignee', 'qty' => 1],
            ['name' => 'Cargador',  'goes_to' => 'assignee', 'qty' => 1],
            // Queda en área compartida
            ['name' => 'Monitor',   'goes_to' => 'area',     'qty' => 1, 'notes' => 'Área compartida de oficina'],
            ['name' => 'Teclado',   'goes_to' => 'area',     'qty' => 1, 'notes' => 'Área compartida de oficina'],
            ['name' => 'Mouse',     'goes_to' => 'area',     'qty' => 1, 'notes' => 'Área compartida de oficina'],
        ], $tipos);

        $this->command->info('Plantillas de asignación AXVOS creadas: Trabajo Remoto, Presencial, Mixto');
    }

    private function createItems(int $templateId, array $items, $tipos): void
    {
        foreach ($items as $i => $item) {
            $typeId = $tipos[$item['name']] ?? null;
            if (!$typeId) continue;

            \App\Models\AssignmentTemplateItem::create([
                'assignment_template_id' => $templateId,
                'asset_type_id'          => $typeId,
                'quantity'               => $item['qty'] ?? 1,
                'goes_to'                => $item['goes_to'],
                'notes'                  => $item['notes'] ?? null,
                'sort_order'             => $i,
            ]);
        }
    }
}
