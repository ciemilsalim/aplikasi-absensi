<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SSOController extends Controller
{
    /**
     * Redirect to LMS with SSO signature.
     */
    public function redirectToLms(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $timestamp = now()->timestamp;
        $userId = $user->id;
        $secret = env('SSO_SECRET_KEY', 'default_sso_secret_key_123'); // Fallback string if not set

        $signature = hash_hmac('sha256', $userId . '|' . $timestamp, $secret);

        $lmsUrl = env('LMS_URL', 'http://localhost:8001');
        
        $url = rtrim($lmsUrl, '/') . '/sso/login?user_id=' . $userId . '&timestamp=' . $timestamp . '&signature=' . $signature;

        return redirect()->away($url);
    }

    /**
     * Handle incoming SSO login from LMS.
     */
    public function login(Request $request)
    {
        $userId = $request->query('user_id');
        $timestamp = $request->query('timestamp');
        $signature = $request->query('signature');
        $secret = env('SSO_SECRET_KEY', 'default_sso_secret_key_123');

        if (!$userId || !$timestamp || !$signature) {
            abort(403, 'Missing SSO parameters.');
        }

        // Check if token is expired (e.g., older than 60 seconds)
        if (now()->timestamp - $timestamp > 60) {
            abort(403, 'SSO Token has expired.');
        }

        $expectedSignature = hash_hmac('sha256', $userId . '|' . $timestamp, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            abort(403, 'Invalid SSO Signature.');
        }

        $user = User::findOrFail($userId);
        
        // Log the user in
        Auth::login($user);

        // Redirect to dashboard or intended route
        return redirect()->route('dashboard');
    }
}
