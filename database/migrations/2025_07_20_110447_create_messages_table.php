<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            // Menghubungkan setiap pesan ke ruang obrolan yang spesifik.
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            // Mencatat siapa yang mengirim pesan (bisa guru atau orang tua).
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('body'); // Isi pesan
            $table->timestamp('read_at')->nullable(); // Penanda apakah pesan sudah dibaca
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('messages'); }
};