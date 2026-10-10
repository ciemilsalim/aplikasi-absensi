<?php

use App\Models\StudentPermit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_permits') && Schema::hasColumn('student_permits', 'pass_code')) {
            StudentPermit::whereNull('pass_code')->chunkById(100, function ($permits) {
                foreach ($permits as $permit) {
                    $permit->ensurePassCode();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: backfilled pass codes do not need to be reversed to null
    }
};
