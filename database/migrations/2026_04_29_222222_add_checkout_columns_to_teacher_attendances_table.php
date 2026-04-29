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
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->timestamp('checkout_time')->nullable()->after('photo_evidence');
            $table->string('checkout_photo_evidence')->nullable()->after('checkout_time');
            $table->decimal('checkout_latitude', 10, 8)->nullable()->after('checkout_photo_evidence');
            $table->decimal('checkout_longitude', 11, 8)->nullable()->after('checkout_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropColumn(['checkout_time', 'checkout_photo_evidence', 'checkout_latitude', 'checkout_longitude']);
        });
    }
};
