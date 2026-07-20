<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SSOController extends Controller
{
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

        // 3. Get target LMS URL
        $lmsUrl = env('LMS_URL', 'http://localhost:8001');

        // 4. Redirect to the target LMS SSO login route
        return redirect()->away(rtrim($lmsUrl, '/') . '/sso/login?token=' . $token);
    }
}
