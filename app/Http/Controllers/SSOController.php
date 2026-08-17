<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SSOController extends Controller
{
    /**
     * Menentukan URL LMS Mokopani secara adaptif (Lokal vs cPanel/Hosting).
     */
    public function getTargetLmsUrl(Request $request): string
    {
        $host = $request->getHost();
        
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'])
            || str_starts_with($host, '192.168.')
            || str_starts_with($host, '10.')
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local');

        if ($isLocalHost) {
            return config('services.lms.local_url', 'http://localhost:8001');
        }

        return config('services.lms.production_url', 'https://mokopani-smpn1biau.zahradev.id');
    }

    /**
     * Redirect to LMS using secure database token SSO.
     */
    public function redirectToLms(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Secure role check: allow 'teacher', 'admin', or 'operator'
        $isTeacher = $user->hasRole('teacher') || ($user->teacher !== null);
        $isAdmin = $user->hasAnyRole(['admin', 'operator']);

        if (!$isTeacher && !$isAdmin) {
            abort(403, 'Anda tidak memiliki hak akses untuk SSO ke LMS Mokopani.');
        }

        // 1. Generate a secure random token
        $token = Str::random(60);

        // 2. Clean up any previous expired tokens for this user
        DB::table('sso_tokens')
            ->where('user_id', $user->id)
            ->orWhere('expires_at', '<', now()->subMinutes(30))
            ->delete();

        // 3. Store the token in the shared database with a 10-minute expiration
        DB::table('sso_tokens')->insert([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Get target LMS URL dynamically based on environment/host
        $lmsUrl = $this->getTargetLmsUrl($request);

        // 5. Redirect to the target LMS SSO login route
        return redirect()->away(rtrim($lmsUrl, '/') . '/sso/login?token=' . $token);
    }
}
