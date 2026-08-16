<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('extracurricular_teacher')) {
            Schema::create('extracurricular_teacher', function (Blueprint $table) {
                $table->id();
                $table->foreignId('extracurricular_id')->constrained()->onDelete('cascade');
                $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
                $table->timestamps();

                $table->unique(['extracurricular_id', 'teacher_id']);
            });
        }

        // Migrate existing teacher_id from extracurriculars table into extracurricular_teacher pivot
        if (Schema::hasColumn('extracurriculars', 'teacher_id')) {
            $existing = DB::table('extracurriculars')
                ->whereNotNull('teacher_id')
                ->get(['id', 'teacher_id', 'created_at', 'updated_at']);

            foreach ($existing as $item) {
                $exists = DB::table('extracurricular_teacher')
                    ->where('extracurricular_id', $item->id)
                    ->where('teacher_id', $item->teacher_id)
                    ->exists();

                if (!$exists) {
                    DB::table('extracurricular_teacher')->insert([
                        'extracurricular_id' => $item->id,
                        'teacher_id' => $item->teacher_id,
                        'created_at' => $item->created_at ?? now(),
                        'updated_at' => $item->updated_at ?? now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurricular_teacher');
    }
};
