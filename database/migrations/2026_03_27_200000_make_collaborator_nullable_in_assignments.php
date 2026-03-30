<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assignments') || !Schema::hasColumn('assignments', 'collaborator_id')) {
            return;
        }

        DB::statement('ALTER TABLE assignments MODIFY collaborator_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('assignments') || !Schema::hasColumn('assignments', 'collaborator_id')) {
            return;
        }

        $fallbackCollaboratorId = DB::table('collaborators')->orderBy('id')->value('id');
        if ($fallbackCollaboratorId !== null) {
            DB::table('assignments')
                ->whereNull('collaborator_id')
                ->update(['collaborator_id' => $fallbackCollaboratorId]);
        }

        DB::statement('ALTER TABLE assignments MODIFY collaborator_id BIGINT UNSIGNED NOT NULL');
    }
};

