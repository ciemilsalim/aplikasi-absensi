<?php

// File: database/migrations/YYYY_MM_DD_HHMMSS_create_admin_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('admin_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_conversation_id')->constrained('admin_conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Pengirim
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('admin_messages'); }
};
