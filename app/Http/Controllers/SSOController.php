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
            return config('services.lms.local_url') 
                ?: env('LMS_LOCAL_URL', env('LMS_URL', 'http://localhost:8001'));
        }

        return config('services.lms.production_url') 
            ?: env('LMS_PRODUCTION_URL', env('LMS_URL', 'https://mokopani-smpn1biau.zahradev.id'));
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

        // Secure role check: only allow 'teacher' or 'admin' or 'operator'
        $isTeacher = $user->hasRole('teacher') || ($user->teacher !== null);
        $isAdmin = $user->hasAnyRole(['admin', 'operator']);

        if (!$isTeacher && !$isAdmin) {
            abort(403, 'Anda tidak memiliki hak akses untuk SSO ke LMS Mokopani.');
        }

        // 1. Generate a secure random token
        $token = Str::random(60);

        // 2. Store the token in the shared database with a 1-minute expiration
        DB::table('sso_tokens')->insert([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => Carbon::now('UTC')->addMinute(),
            'created_at' => Carbon::now('UTC'),
            'updated_at' => Carbon::now('UTC'),
        ]);

        // 3. Get target LMS URL dynamically based on environment/host
        $lmsUrl = $this->getTargetLmsUrl($request);

        // 4. Redirect to the target LMS SSO login route
        return redirect()->away(rtrim($lmsUrl, '/') . '/sso/login?token=' . $token);
    }
}
