<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentOnboardingCompleted
{
    /**
     * Memastikan orang tua yang baru saja registrasi harus menyelesaikan onboarding terlebih dahulu.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ($user->role === 'parent' || $user->hasRole('parent'))) {
            $parent = $user->parent;

            // Jika entri ParentModel belum ada, buat otomatis
            if (!$parent) {
                $parent = \App\Models\ParentModel::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'is_onboarding_completed' => false,
                ]);
            }

            $isOnboardingRoute = $request->routeIs('parent.onboarding.*');

            // Jika belum selesai onboarding & mengakses rute aplikasi lain -> arahkan ke onboarding
            if (!$parent->is_onboarding_completed && !$isOnboardingRoute) {
                return redirect()->route('parent.onboarding.index');
            }

            // Jika sudah selesai onboarding & mencoba buka laman onboarding lagi -> arahkan ke dasbor
            if ($parent->is_onboarding_completed && $isOnboardingRoute) {
                return redirect()->route('parent.dashboard');
            }
        }

        return $next($request);
    }
}
