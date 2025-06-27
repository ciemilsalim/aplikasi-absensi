<?php
// File: database/migrations/YYYY_MM_DD_HHMMSS_create_announcements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Siapa yang membuat
            $table->string('title');
            $table->text('content');
            $table->timestamp('published_at')->nullable(); // Kapan pengumuman ini dipublikasikan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};

