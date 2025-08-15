<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            // Cukup satu foreign key ini untuk menghubungkan Kelas, Mapel, dan Guru
            $table->foreignId('teaching_assignment_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 1:Senin, 2:Selasa, ..., 7:Minggu
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
