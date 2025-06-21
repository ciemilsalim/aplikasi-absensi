<?php
namespace App\Http\Controllers\Parent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class DashboardController extends Controller {
    public function index() {
        $parent = Auth::user()->parent;
        $students = $parent->students()->with(['attendances' => function($query){
            $query->latest()->take(5); // Ambil 5 absensi terakhir
        }, 'schoolClass'])->get();
        return view('parent.dashboard', compact('students'));
    }
}
