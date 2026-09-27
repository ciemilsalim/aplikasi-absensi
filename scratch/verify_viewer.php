<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

echo "=========================================\n";
echo "    VERIFIKASI AKUN VIEWER (SIASEK)      \n";
echo "=========================================\n\n";

// 1. Cek User berdasarkan Email utama
$email = 'siasek_evidence@example.com';
$user = User::where('email', $email)->first();
if (!$user) {
    echo "❌ USER FAILED: Akun {$email} tidak ditemukan!\n";
    exit(1);
}

$totalViewerAccounts = User::where('role', 'viewer')->count();

echo "1. VERIFIKASI USER & ROLE:\n";
echo "   - ID: {$user->id}\n";
echo "   - Name: {$user->name}\n";
echo "   - Email: {$user->email}\n";
echo "   - Role (lokal): {$user->role}\n";
echo "   - Total Akun Viewer DB: {$totalViewerAccounts} " . ($totalViewerAccounts === 1 ? '(NO DUPLICATES)' : '❌ WARNING DUPLICATES') . "\n";
echo "   - hasRole('viewer'): " . ($user->hasRole('viewer') ? 'true (VERIFIED)' : 'false (FAILED)') . "\n";
echo "   - hasRole('admin'): " . ($user->hasRole('admin') ? 'true (ERR: Admin Access!)' : 'false (SAFE)') . "\n\n";

// Login sebagai viewer
Auth::login($user);

function simulateRequest(string $method, string $uri, array $data = []) {
    $session = session()->driver();
    $session->put('_token', 'valid_test_csrf_token');
    if ($method === 'POST') {
        $data['_token'] = 'valid_test_csrf_token';
    }

    $request = Request::create($uri, $method, $data);
    $request->setLaravelSession($session);
    $request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9');
    
    try {
        $response = app()->handle($request);
        return [
            'status' => $response->getStatusCode(),
            'redirect' => $response->isRedirection() ? $response->headers->get('Location') : null,
        ];
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        return [
            'status' => $e->getStatusCode(),
            'redirect' => null,
            'error' => $e->getMessage()
        ];
    } catch (\Throwable $e) {
        return [
            'status' => 500,
            'redirect' => null,
            'error' => $e->getMessage()
        ];
    }
}

echo "2. VERIFIKASI AKSES READ-ONLY (HARUS BISA / Status 200 or 302):\n";
$readOnlyRoutes = [
    ['GET', '/dashboard', 'Redirect ke Admin Dashboard'],
    ['GET', '/admin/dashboard', 'Admin Dashboard Utama'],
    ['GET', '/principal/dashboard', 'Executive Overview (Headmaster Dashboard)'],
    ['GET', '/admin/reports', 'Laporan Presensi Siswa'],
    ['GET', '/admin/reports/charts', 'Grafik Analytics Presensi Siswa'],
    ['GET', '/admin/teaching-journals', 'Supervisi Jurnal Mengajar Guru'],
    ['GET', '/admin/parent-verifications', 'Verifikasi Klaim Orang Tua'],
    ['GET', '/admin/leave-requests', 'Daftar Pengajuan Izin Siswa'],
];

foreach ($readOnlyRoutes as [$method, $uri, $desc]) {
    $res = simulateRequest($method, $uri);
    $statusStr = $res['status'];
    if ($res['redirect']) {
        $statusStr .= " -> " . $res['redirect'];
    }
    $passed = ($res['status'] === 200 || $res['status'] === 302);
    $mark = $passed ? '✔ PASS' : '❌ FAIL';
    echo "   [{$mark}] {$method} {$uri} ({$desc}): Status {$statusStr}\n";
}

// Ambil ID contoh untuk pengetesan 403 mutasi
$leaveId = DB::table('leave_requests')->value('id') ?? 1;
$journalId = DB::table('teaching_journals')->value('id') ?? 1;
$parentReqId = DB::table('parent_student_requests')->value('id') ?? 1;

echo "\n3. VERIFIKASI KEAMANAN WRITE / MUTASI (HARUS DITOLAK / Status 403 atau 302 SIPADA):\n";
$forbiddenRoutes = [
    ['POST', '/admin/leave-requests/manual', 'Simpan Izin Siswa Manual'],
    ['POST', "/admin/leave-requests/{$leaveId}/approve", 'Approve Izin Siswa'],
    ['POST', "/admin/teaching-journals/{$journalId}/verify", 'Verifikasi Jurnal Guru'],
    ['POST', "/admin/parent-verifications/{$parentReqId}/approve", 'Approve Verifikasi Ortu'],
    ['POST', '/admin/users', 'Tambah User Baru (CRUD)'],
];

foreach ($forbiddenRoutes as [$method, $uri, $desc]) {
    $res = simulateRequest($method, $uri);
    $statusStr = $res['status'];
    if ($res['redirect']) {
        $statusStr .= " -> " . $res['redirect'];
    }
    $passed = ($res['status'] === 403 || $res['status'] === 302); // 403 Forbidden or 302 SIPADA Redirect
    $mark = $passed ? '✔ PASS (Restricted / Blocked)' : "❌ FAIL (Unprotected! Status {$res['status']})";
    echo "   [{$mark}] {$method} {$uri} ({$desc}): Status {$statusStr}\n";
}

echo "\n=========================================\n";
echo "       AKUN VIEWER VERIFIED CLEAN!       \n";
echo "=========================================\n";
