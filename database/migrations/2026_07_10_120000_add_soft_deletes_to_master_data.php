<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['users', 'students', 'teachers', 'parents', 'school_classes'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = ['users', 'students', 'teachers', 'parents', 'school_classes'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
