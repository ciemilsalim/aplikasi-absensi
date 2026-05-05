<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncStudentUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:sync-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create user accounts for students who do not have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $students = \App\Models\Student::whereNull('user_id')->get();
        $count = 0;

        foreach ($students as $student) {
            // Cek apakah user dengan email ini sudah ada (email format nis@mokopani.com)
            $email = $student->nis . '@mokopani.com';
            $user = \App\Models\User::where('email', $email)->first();

            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $student->name,
                    'email' => $email,
                    'password' => bcrypt($student->nis), // Password default adalah NIS
                    'role' => 'student',
                ]);
            }

            $student->update(['user_id' => $user->id]);
            $count++;
        }

        $this->info("Successfully created/linked $count student user accounts.");
    }
}
