<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckSystemCommand extends Command
{
    protected $signature = 'system:check';
    protected $description = 'Verifica todas las tablas, migraciones y estructura del sistema';

    public function handle()
    {
        $this->info('========================================');
        $this->info('🔍 VERIFICACIÓN COMPLETA DEL SISTEMA');
        $this->info('========================================');
        $this->newLine();

        // ===========================================
        // 1. VERIFICAR MIGRACIONES EJECUTADAS
        // ===========================================
        $this->info('📋 MIGRACIONES EJECUTADAS:');
        
        if (Schema::hasTable('migrations')) {
            $migrations = DB::table('migrations')->orderBy('id')->get();
            
            if ($migrations->count() > 0) {
                foreach ($migrations as $migration) {
                    $this->line("   ✅ {$migration->migration}");
                }
                $this->info("   Total: {$migrations->count()} migraciones");
            } else {
                $this->error('   ❌ No hay migraciones registradas');
            }
        } else {
            $this->error('   ❌ Tabla migrations NO EXISTE');
        }
        
        $this->newLine();

        // ===========================================
        // 2. VERIFICAR TABLAS EXISTENTES
        // ===========================================
        $this->info('📊 TABLAS EN BASE DE DATOS:');
        
        try {
            $tables = DB::select('SHOW TABLES');
            $database = env('DB_DATABASE');
            $key = "Tables_in_{$database}";
            
            $existingTables = [];
            foreach ($tables as $table) {
                $tableName = $table->$key;
                $existingTables[] = $tableName;
                $this->line("   ✅ {$tableName}");
            }
            
            $this->info("   Total: " . count($existingTables) . " tablas");
        } catch (\Exception $e) {
            $this->error('   ❌ Error al leer tablas: ' . $e->getMessage());
        }
        
        $this->newLine();

        // ===========================================
        // 3. TABLAS REQUERIDAS VS EXISTENTES
        // ===========================================
        $this->info('🎯 VERIFICACIÓN DE TABLAS REQUERIDAS:');
        
        $requiredTables = [
            'users' => 'Usuarios del sistema',
            'roles' => 'Roles de permisos',
            'permissions' => 'Permisos',
            'model_has_roles' => 'Relación usuarios-roles',
            'model_has_permissions' => 'Relación usuarios-permisos',
            'role_has_permissions' => 'Relación roles-permisos',
            'branches' => 'Sucursales',
            'departments' => 'Departamentos',
            'asset_types' => 'Tipos de activo',
            'assets' => 'Activos',
            'statuses' => 'Estados',
            'categories' => 'Categorías',
            'locations' => 'Ubicaciones',
            'collaborators' => 'Colaboradores',
            'workstations' => 'Estaciones de trabajo',
            'assignments' => 'Asignaciones',
            'assignment_history' => 'Historial de asignaciones',
            'disposals' => 'Bajas de activos',
            'reports' => 'Reportes generados',
            'asset_audits' => 'Auditoría de activos',
        ];
        
        $missingTables = [];
        $existingTablesList = $existingTables ?? [];
        
        foreach ($requiredTables as $table => $description) {
            if (in_array($table, $existingTablesList)) {
                $this->info("   ✅ {$table} - {$description}");
            } else {
                $this->error("   ❌ {$table} - {$description} - FALTA");
                $missingTables[] = $table;
            }
        }
        
        $this->newLine();

        // ===========================================
        // 4. VERIFICAR COLUMNAS EN asset_types
        // ===========================================
        $this->info('🔧 VERIFICANDO ESTRUCTURA DE asset_types:');
        
        if (Schema::hasTable('asset_types')) {
            $requiredColumns = [
                'id' => '✅',
                'name' => '✅',
                'code' => '✅',
                'prefix' => '⚠️ NUEVA - ¿Agregaste?',
                'category' => '✅',
                'active' => '✅',
                'created_by' => '⚠️ NUEVA - ¿Agregaste?',
                'updated_by' => '⚠️ NUEVA - ¿Agregaste?',
                'created_at' => '✅',
                'updated_at' => '✅',
            ];
            
            foreach ($requiredColumns as $column => $status) {
                if (Schema::hasColumn('asset_types', $column)) {
                    $this->line("   ✅ {$column}");
                } else {
                    $this->error("   ❌ {$column} {$status}");
                }
            }
        } else {
            $this->error('   ❌ Tabla asset_types NO EXISTE');
        }
        
        $this->newLine();

        // ===========================================
        // 5. VERIFICAR COLUMNAS EN assets
        // ===========================================
        $this->info('🔧 VERIFICANDO ESTRUCTURA DE assets:');
        
        if (Schema::hasTable('assets')) {
            $requiredColumns = [
                'id' => '✅',
                'asset_type_id' => '✅',
                'internal_code' => '✅',
                'asset_tag' => '✅',
                'brand' => '✅',
                'model' => '✅',
                'serial' => '✅',
                'property_type' => '✅',
                'status_id' => '✅',
                'branch_id' => '✅',
                'purchase_date' => '⚠️ NUEVA - ¿Agregaste?',
                'purchase_value' => '⚠️ NUEVA - ¿Agregaste?',
                'current_value' => '⚠️ NUEVA - ¿Agregaste?',
                'warranty_end' => '⚠️ NUEVA - ¿Agregaste?',
                'depreciation_rate' => '⚠️ NUEVA - ¿Agregaste?',
                'last_maintenance' => '⚠️ NUEVA - ¿Agregaste?',
                'next_maintenance' => '⚠️ NUEVA - ¿Agregaste?',
                'observations' => '✅',
                'created_at' => '✅',
                'updated_at' => '✅',
            ];
            
            foreach ($requiredColumns as $column => $status) {
                if (Schema::hasColumn('assets', $column)) {
                    $this->line("   ✅ {$column}");
                } else {
                    $this->error("   ❌ {$column} {$status}");
                }
            }
        } else {
            $this->error('   ❌ Tabla assets NO EXISTE');
        }
        
        $this->newLine();

        // ===========================================
        // 6. VERIFICAR RELACIONES (FOREIGN KEYS)
        // ===========================================
        $this->info('🔗 VERIFICANDO FOREIGN KEYS EN assets:');
        
        try {
            $foreignKeys = DB::select("
                SELECT 
                    COLUMN_NAME,
                    CONSTRAINT_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                    AND TABLE_NAME = 'assets'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [env('DB_DATABASE')]);
            
            if (count($foreignKeys) > 0) {
                foreach ($foreignKeys as $fk) {
                    $this->line("   ✅ {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}");
                }
            } else {
                $this->error('   ❌ No hay FOREIGN KEYS en assets');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error al verificar FKs: ' . $e->getMessage());
        }
        
        $this->newLine();

        // ===========================================
        // 7. RESUMEN FINAL
        // ===========================================
        $this->info('========================================');
        $this->info('📌 RESUMEN FINAL:');
        $this->info('========================================');
        
        if (count($missingTables) > 0) {
            $this->error('   ❌ FALTAN ' . count($missingTables) . ' TABLAS CRÍTICAS:');
            foreach ($missingTables as $table) {
                $this->line("      - {$table}");
            }
            $this->newLine();
            $this->warn('   ⚠️  EJECUTA: php artisan make:migration create_' . $missingTables[0] . '_table');
        } else {
            $this->info('   ✅ ¡TODAS LAS TABLAS REQUERIDAS EXISTEN!');
        }
        
        $this->newLine();
        $this->info('✅ Verificación completada');
        $this->info('========================================');
        
        return 0;
    }
}