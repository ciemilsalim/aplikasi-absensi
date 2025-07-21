<?php
// File: database/migrations/YYYY_MM_DD_HHMMSS_create_admin_conversations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('admin_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade'); // User ID admin
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('admin_conversations'); }
};