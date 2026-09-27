<?php
/**
 * Live SIASEK Application Billing Extractor (READ-ONLY)
 * Extracts Active Student Count from SIASEK Live Admin Dashboard UI
 */

function extractLiveBillingData() {
    $envFile = __DIR__ . '/../../../../.env.siasek-bos';
    $credentials = [];

    if (file_exists($envFile)) {
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
        'active_student_count' => $activeStudentCount,
        'billing_capture_time' => date('Y-m-d H:i:s'),
        'billing_cutoff' => date('Y-m-d'),
        'screenshot_ref' => 'bukti_01_dashboard.png'
    ];
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo json_encode(extractLiveBillingData(), JSON_PRETTY_PRINT);
}
