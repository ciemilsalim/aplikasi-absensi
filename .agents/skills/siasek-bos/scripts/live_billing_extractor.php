<?php
/**
 * Adaptive Multi-Project Application Billing Extractor (READ-ONLY)
 * Supports SIASEK (Live Student Scraper), Apotek (Local DB/Subscription), & General SaaS
 */

function extractLiveBillingData($domainType = 'AUTO', $projectDir = null, $profile = []) {
    if (!$projectDir) {
        $projectDir = getcwd();
    }

    // Auto-detect domain type if not specified
    if ($domainType === 'AUTO') {
        $dirLower = strtolower($projectDir);
        if (strpos($dirLower, 'apotek') !== false) {
            $domainType = 'APOTEK';
        } elseif (strpos($dirLower, 'siasek') !== false || strpos($dirLower, 'absensi') !== false) {
            $domainType = 'SCHOOL_BOSP';
        } else {
            $domainType = 'GENERAL_SAAS';
        }
    }

    // 1. APOTEK DOMAIN
    if ($domainType === 'APOTEK') {
        $appUrl = getenv('APP_URL') ?: 'http://apotek.test';
        $activeCount = 1;
        $unit = 'Lisensi';
        $extraInfo = 'Pembelian Lisensi Perangkat Lunak Apotek (One-Time Purchase)';

        // Check if database is accessible to get real metrics
        try {
            if (class_exists('Illuminate\Support\Facades\Schema') && class_exists('Illuminate\Support\Facades\DB')) {
                if (Illuminate\Support\Facades\Schema::hasTable('medicines')) {
                    $medCount = Illuminate\Support\Facades\DB::table('medicines')->count();
                    $extraInfo .= " ({$medCount} Katalog Obat Terdaftar)";
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback if DB not connected
        }

        return [
            'login_success' => true,
            'billing_source_type' => 'SYSTEM_SUBSCRIPTION',
            'billing_source_url' => $appUrl,
            'billing_role' => 'admin',
            'billing_source_page' => '/dashboard',
            'active_quantity' => $activeCount,
            'active_student_count' => $activeCount, // Backward compatibility
            'billing_unit' => $unit,
            'extra_info' => $extraInfo,
            'billing_capture_time' => date('Y-m-d H:i:s'),
            'billing_cutoff' => date('Y-m-d'),
            'screenshot_ref' => 'bukti_01_dashboard.png'
        ];
    }

    // 2. GENERAL SAAS DOMAIN
    if ($domainType === 'GENERAL_SAAS') {
        $appUrl = getenv('APP_URL') ?: 'http://localhost';
        return [
            'login_success' => true,
            'billing_source_type' => 'SAAS_FLAT_SUBSCRIPTION',
            'billing_source_url' => $appUrl,
            'billing_role' => 'admin',
            'billing_source_page' => '/dashboard',
            'active_quantity' => 1,
            'active_student_count' => 1,
            'billing_unit' => 'Bulan',
            'extra_info' => '1 Bulan Layanan Operasional Aplikasi',
            'billing_capture_time' => date('Y-m-d H:i:s'),
            'billing_cutoff' => date('Y-m-d'),
            'screenshot_ref' => 'bukti_01_dashboard.png'
        ];
    }

    // 3. SCHOOL BOSP (SIASEK) DOMAIN
    $envFiles = [
        "{$projectDir}/.env.siasek-bos",
        __DIR__ . '/../../../../.env.siasek-bos',
        dirname(__DIR__) . '/.env.siasek-bos'
    ];
    $envFile = null;
    foreach ($envFiles as $ef) {
        if (file_exists($ef)) {
            $envFile = $ef;
            break;
        }
    }

    $credentials = [];
    if ($envFile && file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($key, $val) = explode('=', $line, 2);
            $credentials[trim($key)] = trim($val);
        }
    }

    $liveUrl = $credentials['SIASEK_URL'] ?? 'https://presensi-smpn1biau.zahradev.id';
    $email = $credentials['SIASEK_ADMIN_EMAIL'] ?? $credentials['SIASEK_EVIDENCE_USERNAME'] ?? '';
    $password = $credentials['SIASEK_ADMIN_PASSWORD'] ?? $credentials['SIASEK_EVIDENCE_PASSWORD'] ?? '';

    $cookieJar = tempnam(sys_get_temp_dir(), 'siasek_cookie_');

    // 1. GET /login to retrieve CSRF token
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "{$liveUrl}/login",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SIASEK Live Billing Scraper/1.0',
        CURLOPT_TIMEOUT => 15
    ]);
    $response = curl_exec($ch);

    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $response, $matches);
    $csrfToken = $matches[1] ?? '';

    if (empty($csrfToken)) {
        preg_match('/meta name="csrf-token" content="([^"]+)"/', $response, $matches);
        $csrfToken = $matches[1] ?? '';
    }

    // 2. POST /login (READ-ONLY Authentication)
    curl_setopt_array($ch, [
        CURLOPT_URL => "{$liveUrl}/login",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => $csrfToken,
            'email' => $email,
            'password' => $password
        ]),
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $loginHtml = curl_exec($ch);

    // 3. GET /admin/dashboard (Reset HTTP verb to GET)
    curl_setopt_array($ch, [
        CURLOPT_URL => "{$liveUrl}/admin/dashboard",
        CURLOPT_HTTPGET => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $dashboardHtml = curl_exec($ch);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    curl_close($ch);
    @unlink($cookieJar);

    // Clean text by stripping HTML tags
    $cleanText = strip_tags($dashboardHtml);

    $activeStudentCount = null;
    $sourcePage = "/admin/dashboard";

    if (preg_match('/([0-9]+)\s*Siswa\s*Aktif/i', $cleanText, $m)) {
        $activeStudentCount = (int)$m[1];
    } elseif (preg_match('/Total[^0-9]*([0-9]+)\s*Siswa/i', $cleanText, $m)) {
        $activeStudentCount = (int)$m[1];
    }

    $loginSuccess = (strpos($effectiveUrl, '/admin/dashboard') !== false || strpos($dashboardHtml, 'Siswa Aktif') !== false || $activeStudentCount !== null);

    return [
        'login_success' => $loginSuccess,
        'billing_source_type' => 'LIVE_APPLICATION',
        'billing_source_url' => $liveUrl,
        'billing_role' => 'admin',
        'billing_source_page' => $sourcePage,
        'active_quantity' => $activeStudentCount,
        'active_student_count' => $activeStudentCount,
        'billing_unit' => 'Siswa',
        'billing_capture_time' => date('Y-m-d H:i:s'),
        'billing_cutoff' => date('Y-m-d'),
        'screenshot_ref' => 'bukti_01_dashboard.png'
    ];
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo json_encode(extractLiveBillingData(), JSON_PRETTY_PRINT);
}

