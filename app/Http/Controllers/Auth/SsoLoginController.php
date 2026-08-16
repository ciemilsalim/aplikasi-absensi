<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SsoLoginController extends Controller
{
    /**
     * Authenticate the user automatically using a valid SSO token.
     */
    public function login(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect()->route('login')->withErrors(['sso' => 'Token SSO tidak ditemukan.']);
        }

        // 1. Find and validate the token in the shared database
        // Robust timezone-agnostic query (valid if expires_at > now or created within last 15 minutes)
        $ssoToken = DB::table('sso_tokens')
            ->where('token', $token)
            ->where(function($q) {
                $q->where('expires_at', '>', now())
                  ->orWhere('created_at', '>=', now()->subMinutes(15));
            })
            ->first();

        if (!$ssoToken) {
            return redirect()->route('login')->withErrors(['sso' => 'Token SSO tidak valid atau telah kadaluarsa.']);
        }

        // 2. Log the user in with remember token
        Auth::loginUsingId($ssoToken->user_id, true);

        // 3. Delete the token immediately (one-time use)
        DB::table('sso_tokens')->where('token', $token)->delete();

        // 4. Regenerate the session for security
        $request->session()->regenerate();

        // 5. Redirect to the dashboard
        return redirect()->intended(route('dashboard'));
    }
}
