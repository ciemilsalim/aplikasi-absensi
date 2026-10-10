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
            if (!Schema::hasColumn('teacher_attendances', 'gps_accuracy')) {
                $table->decimal('gps_accuracy', 8, 2)->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('teacher_attendances', 'is_mock_location')) {
                $table->boolean('is_mock_location')->default(false)->after('gps_accuracy');
            }
            if (!Schema::hasColumn('teacher_attendances', 'device_info')) {
                $table->string('device_info', 255)->nullable()->after('is_mock_location');
            }
            if (!Schema::hasColumn('teacher_attendances', 'checkout_gps_accuracy')) {
                $table->decimal('checkout_gps_accuracy', 8, 2)->nullable()->after('checkout_longitude');
            }
            if (!Schema::hasColumn('teacher_attendances', 'checkout_is_mock_location')) {
                $table->boolean('checkout_is_mock_location')->default(false)->after('checkout_gps_accuracy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['gps_accuracy', 'is_mock_location', 'device_info', 'checkout_gps_accuracy', 'checkout_is_mock_location'] as $col) {
                if (Schema::hasColumn('teacher_attendances', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
