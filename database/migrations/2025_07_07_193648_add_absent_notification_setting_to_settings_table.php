<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insert([
            ['key' => 'send_absent_notification', 'value' => 'off'], // Default 'off'
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'send_absent_notification')->delete();
    }
};
