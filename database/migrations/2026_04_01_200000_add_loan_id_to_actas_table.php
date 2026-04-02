<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actas', function (Blueprint $table) {
            if (! Schema::hasColumn('actas', 'loan_id')) {
                $table->unsignedBigInteger('loan_id')->nullable()->after('assignment_id');
                $table->foreign('loan_id')->references('id')->on('loans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('actas', function (Blueprint $table) {
            if (Schema::hasColumn('actas', 'loan_id')) {
                $table->dropForeign(['loan_id']);
                $table->dropColumn('loan_id');
            }
        });
    }
};
