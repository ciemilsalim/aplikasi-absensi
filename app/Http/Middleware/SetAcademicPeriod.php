<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Semester;

class SetAcademicPeriod
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('active_semester_id')) {
            if (!session()->has('active_academic_year_id')) {
                $semester = Semester::find(session('active_semester_id'));
                if ($semester) {
                    session(['active_academic_year_id' => $semester->academic_year_id]);
                }
            }
        } else {
            $activeSemester = Semester::where('is_active', true)->first();
            
            if ($activeSemester) {
                session(['active_semester_id' => $activeSemester->id]);
                session(['active_academic_year_id' => $activeSemester->academic_year_id]);
            }
        }

        return $next($request);
    }
}
