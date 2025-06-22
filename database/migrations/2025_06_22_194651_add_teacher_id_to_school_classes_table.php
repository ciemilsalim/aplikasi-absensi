<?php
// Jalankan: php artisan make:migration add_teacher_id_to_school_classes_table --table=school_classes

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            // Menambahkan foreign key teacher_id.
            // Satu guru hanya bisa menjadi wali untuk satu kelas (unique).
            // Jika guru dihapus, kolom ini akan menjadi null.
            $table->foreignId('teacher_id')
                  ->nullable()
                  ->unique()
                  ->constrained('teachers')
                  ->onDelete('set null')
                  ->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn('teacher_id');
        });
    }
};
