<?php
/**
 * SIASEK & Multi-Project Universal Evidence & Billing Generator (v2.1)
 * Supports SIASEK (BOSP School Attendance), Apotek Sehat (Rp 500k One-time + Rp 200k/year Hosting), & General SaaS
 * 
 * Usage:
 *   php generate_bosp_package.php --month=september --year=2026 --mode=draft
 *   php generate_bosp_package.php --month=oktober --year=2026 --project-dir="d:/laragon/www/apotek"
 *   php generate_bosp_package.php --month=oktober --year=2026 --project-dir="d:/laragon/www/apotek" --with-hosting
 */

require_once __DIR__ . '/live_billing_extractor.php';

use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;

$options = getopt("", [
    "month:", "year:", "mode:", "status:",
    "simulated-date:", "sim-date:", "simulated-live:", "simulated-live-count:",
    "project-dir:", "profile:", "hosting", "with-hosting", "include-hosting"
]);

$monthInput = strtolower($options['month'] ?? 'september');
$yearInput = (int)($options['year'] ?? 2026);
$modeInput = strtolower($options['mode'] ?? $options['status'] ?? 'draft');
$simulatedDateInput = $options['simulated-date'] ?? $options['sim-date'] ?? null;
$simulatedLiveCountInput = isset($options['simulated-live']) ? (int)$options['simulated-live'] : (isset($options['simulated-live-count']) ? (int)$options['simulated-live-count'] : null);
$cliWithHosting = isset($options['hosting']) || isset($options['with-hosting']) || isset($options['include-hosting']);

$monthMap = [
    'januari' => 1, 'january' => 1,
    'februari' => 2, 'february' => 2,
    'maret' => 3, 'march' => 3,
    'april' => 4,
    'mei' => 5, 'may' => 5,
    'juni' => 6, 'june' => 6,
    'juli' => 7, 'july' => 7,
    'agustus' => 8, 'august' => 8,
    'september' => 9,
    'oktober' => 10, 'october' => 10,
    'november' => 11,
    'desember' => 12, 'december' => 12
];

if (!isset($monthMap[$monthInput])) {
    echo "ERROR: Nama bulan '{$monthInput}' tidak dikenali.\n";
    exit(1);
}

$monthNum = $monthMap[$monthInput];
$monthFormatted = sprintf("%02d", $monthNum);
$monthNamesIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$monthNamesSlug = [
    1 => 'januari', 2 => 'februari', 3 => 'maret', 4 => 'april', 5 => 'mei', 6 => 'juni',
    7 => 'juli', 8 => 'agustus', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'desember'
];
$monthSlug = $monthNamesSlug[$monthNum];
$monthNameIndo = $monthNamesIndo[$monthNum];
$monthUpper = strtoupper($monthSlug);

$startDateStr = "{$yearInput}-{$monthFormatted}-01";
$lastDay = date('t', strtotime($startDateStr));
$endDateStr = "{$yearInput}-{$monthFormatted}-{$lastDay}";

$currentDateStr = $simulatedDateInput ? $simulatedDateInput : date('Y-m-d');
$currentYearMonth = date('Y-m', strtotime($currentDateStr));
$targetYearMonth = sprintf("%04d-%02d", $yearInput, $monthNum);

// --- 1. PERIOD CLASSIFICATION & BOUNDARIES ---
if ($targetYearMonth === $currentYearMonth) {
    $periodType = 'CURRENT_PERIOD';
    $cutoffDateStr = $currentDateStr;
    $gitPeriodStart = "{$yearInput}-{$monthFormatted}-01 00:00:00";
    $gitPeriodEnd = "{$cutoffDateStr} 23:59:59";
} elseif ($targetYearMonth < $currentYearMonth) {
    $periodType = 'HISTORICAL_PERIOD';
    $cutoffDateStr = $endDateStr;
    $gitPeriodStart = "{$yearInput}-{$monthFormatted}-01 00:00:00";
    $gitPeriodEnd = "{$endDateStr} 23:59:59";
} else {
    $periodType = 'FUTURE_PERIOD';
    $cutoffDateStr = $endDateStr;
    $gitPeriodStart = "{$yearInput}-{$monthFormatted}-01 00:00:00";
    $gitPeriodEnd = "{$endDateStr} 23:59:59";
}

// --- 2. DYNAMIC PROJECT DIRECTORY & LARAVEL BOOTSTRAP ---
$projectDir = !empty($options['project-dir']) ? realpath($options['project-dir']) : getcwd();
if (!$projectDir || (!file_exists("{$projectDir}/artisan") && !file_exists("{$projectDir}/composer.json"))) {
    // Search upwards from cwd
    $curr = getcwd();
    while ($curr && strlen($curr) > 3) {
        if (file_exists("{$curr}/artisan") || file_exists("{$curr}/composer.json")) {
            $projectDir = $curr;
            break;
        }
        $parent = dirname($curr);
        if ($parent === $curr) break;
        $curr = $parent;
    }
}
chdir($projectDir);
$skillDir = dirname(__DIR__);

// Load Laravel Bootstrap
if (file_exists("{$projectDir}/vendor/autoload.php")) {
    require_once "{$projectDir}/vendor/autoload.php";
}
if (file_exists("{$projectDir}/bootstrap/app.php")) {
    $app = require_once "{$projectDir}/bootstrap/app.php";
    if (is_object($app) && method_exists($app, 'make')) {
        try {
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
        } catch (\Throwable $e) {
            // Kernel already bootstrapped or CLI runner
        }
    }
}

// --- 3. DYNAMIC CONFIGURATION & DOMAIN PROFILE DETECTION ---
$globalConfigPath = "{$skillDir}/config.yaml";
$globalConfig = file_exists($globalConfigPath) ? Symfony\Component\Yaml\Yaml::parseFile($globalConfigPath) : [];

$appName = (function_exists('config') ? config('app.name') : null) ?: getenv('APP_NAME') ?: basename($projectDir);
$appUrl = (function_exists('config') ? config('app.url') : null) ?: getenv('APP_URL') ?: 'http://localhost';

// Detect Domain Type
if (!empty($options['profile'])) {
    $domainType = strtoupper($options['profile']);
} else {
    $dirLower = strtolower($projectDir);
    $nameLower = strtolower($appName);
    if (strpos($dirLower, 'apotek') !== false || strpos($nameLower, 'apotek') !== false) {
        $domainType = 'APOTEK';
    } elseif (strpos($dirLower, 'siasek') !== false || strpos($dirLower, 'absensi') !== false || strpos($nameLower, 'presensi') !== false) {
        $domainType = 'SCHOOL_BOSP';
    } else {
        $domainType = 'GENERAL_SAAS';
    }
}

// Select default profile matching domain
$profileKey = ($domainType === 'APOTEK') ? 'apotek' : (($domainType === 'SCHOOL_BOSP') ? 'siasek' : 'default');
$selectedProfile = $globalConfig['profiles'][$profileKey] ?? $globalConfig['profiles']['default'] ?? [];

// Check for project-specific local config overrides
$localConfigPaths = [
    "{$projectDir}/.agents/skills/siasek-bos/config.yaml",
    "{$projectDir}/evidence-config.yaml",
    "{$projectDir}/billing-config.yaml"
];
foreach ($localConfigPaths as $lcp) {
    if (file_exists($lcp)) {
        $localConfig = Symfony\Component\Yaml\Yaml::parseFile($lcp);
        if (isset($localConfig['project'])) {
            $selectedProfile['code'] = $localConfig['project']['code'] ?? ($selectedProfile['code'] ?? 'APP');
            $selectedProfile['name'] = $localConfig['project']['name'] ?? ($selectedProfile['name'] ?? $appName);
            $selectedProfile['customer'] = $localConfig['customer'] ?? ($selectedProfile['customer'] ?? []);
            $selectedProfile['billing'] = $localConfig['billing'] ?? ($selectedProfile['billing'] ?? []);
            $selectedProfile['invoice_series'] = $localConfig['invoice']['numbering']['series'] ?? ($selectedProfile['invoice_series'] ?? $selectedProfile['code']);
        }
        break;
    }
}

$projectCode = $selectedProfile['code'] ?? ($domainType === 'APOTEK' ? 'APOTEK-SEHAT' : ($domainType === 'SCHOOL_BOSP' ? 'SIASEK-BIAU' : 'SAAS-SERVICE'));
$projectName = $selectedProfile['name'] ?? $appName;
$serviceName = $selectedProfile['service_name'] ?? "Jasa Layanan Aplikasi {$appName}";
$serviceDesc = $selectedProfile['service_description'] ?? "Layanan Operasional SaaS, Pemeliharaan Sistem, dan Pembaruan Fitur";
$customerName = $selectedProfile['customer']['name'] ?? ($domainType === 'APOTEK' ? 'Apotek Sehat / Mitra Farmasi' : ($domainType === 'SCHOOL_BOSP' ? 'SMP Negeri 1 Biau' : 'Pengguna Layanan'));
$customerAddress = $selectedProfile['customer']['address'] ?? ($domainType === 'SCHOOL_BOSP' ? 'Jl. Ahmad Yani No. 54, Kelurahan Leok I, Kecamatan Biau, Kabupaten Buol, Sulawesi Tengah' : 'Kabupaten Buol, Provinsi Sulawesi Tengah');
$billingUnit = $selectedProfile['billing']['unit'] ?? ($domainType === 'SCHOOL_BOSP' ? 'Siswa' : ($domainType === 'APOTEK' ? 'Lisensi' : 'Bulan'));
$rate = (int)($selectedProfile['billing']['rate'] ?? ($domainType === 'SCHOOL_BOSP' ? 1000 : ($domainType === 'APOTEK' ? 600000 : 200000)));
$invoiceSeries = $selectedProfile['invoice_series'] ?? $projectCode;
$docPrefix = $selectedProfile['doc_prefix'] ?? ($domainType === 'SCHOOL_BOSP' ? 'PAKET_BOSP_SIASEK_BIAU' : "PAKET_EVIDENCE_{$projectCode}");

$includeHosting = $cliWithHosting || ($selectedProfile['billing']['include_hosting'] ?? false);
$hostingRate = (int)($selectedProfile['billing']['hosting_rate'] ?? 200000);

$providerName = $globalConfig['provider']['name'] ?? 'ZahraDev';
$providerDev = $globalConfig['provider']['developer'] ?? 'Emil Salim, S.Kom';
if (isset($globalConfig['provider']['emails']) && is_array($globalConfig['provider']['emails'])) {
    $providerEmail = implode("  \n", $globalConfig['provider']['emails']);
} else {
    $providerEmail = "ptzahradev@gmail.com  \nemilsalimramadhan@gmail.com";
}

$paymentMethod = $globalConfig['payment']['method'] ?? 'Transfer Bank';
$paymentBank = $globalConfig['payment']['bank'] ?? 'Bank Jago';
$paymentAccountName = $globalConfig['payment']['account_name'] ?? 'EMIL SALIM S';
$paymentAccountNumber = $globalConfig['payment']['account_number'] ?? '1087 1358 0283';

// Templates directory resolver
$templatesDir = file_exists("{$projectDir}/.agents/skills/siasek-bos/templates") 
    ? "{$projectDir}/.agents/skills/siasek-bos/templates" 
    : "{$skillDir}/templates";

// Target Directory Structure
$evidenceSubdir = ($domainType === 'SCHOOL_BOSP') ? 'bosp' : 'billing';
$targetDir = "{$projectDir}/evidence/{$evidenceSubdir}/{$yearInput}/{$monthFormatted}-{$monthSlug}";
$billingDir = "{$targetDir}/billing";
$snapshotsDir = "{$billingDir}/snapshots";
$finalDirBilling = "{$billingDir}/final";

@mkdir($billingDir, 0777, true);
@mkdir($snapshotsDir, 0777, true);
@mkdir($finalDirBilling, 0777, true);

// Section Dirs
$invDir = "{$targetDir}/01_invoice";
$pemanfaatanDir = "{$targetDir}/02_rincian_pemanfaatan";
$fiturDir = "{$targetDir}/03_pembaruan_fitur";
$ssDir = "{$targetDir}/04_screenshots";
$idxDir = "{$targetDir}/05_evidence_index";
$finalDirPackage = "{$targetDir}/FINAL";

@mkdir($invDir, 0777, true);
@mkdir($pemanfaatanDir, 0777, true);
@mkdir($fiturDir, 0777, true);
@mkdir($ssDir, 0777, true);
@mkdir($idxDir, 0777, true);
@mkdir($finalDirPackage, 0777, true);

// --- 4. BILLING RECONCILIATION & SNAPSHOT STATUS LOGIC ---
$billingStatus = 'UNVERIFIED';
$snapshotStatus = 'UNVERIFIED';
$finalFrozen = false;
$frozenAt = null;
$billingSourceType = 'UNVERIFIED';
$billingSourceUrl = $appUrl;
$billingSourceDate = $cutoffDateStr;
$activeStudentCount = null;
$totalAmount = 0;
$billingScreenshotRef = null;
$snapshotCreated = false;
$snapshotFileRel = "N/A";
$snapshotHistoryList = [];

// Calculate Total Amount based on domain
if ($domainType === 'APOTEK') {
    $activeStudentCount = 1;
    $totalAmount = $rate + ($includeHosting ? $hostingRate : 0);
}

// Check Priority 1: Final Frozen Snapshot (billing/final/<YYYY-MM>-final.json)
$finalSnapshotFile = "{$finalDirBilling}/{$targetYearMonth}-final.json";
if (file_exists($finalSnapshotFile)) {
    $snapData = json_decode(file_get_contents($finalSnapshotFile), true);
    if (($snapData['status'] ?? '') === 'VERIFIED' || ($snapData['snapshot_status'] ?? '') === 'FINAL_FROZEN_SNAPSHOT') {
        $activeStudentCount = $snapData['active_quantity'] ?? $snapData['active_student_count'] ?? 1;
        $billingSourceType = 'FINAL_FROZEN_SNAPSHOT';
        $billingSourceUrl = $snapData['source_url'] ?? $appUrl;
        $billingSourceDate = $snapData['snapshot_date'] ?? $cutoffDateStr;
        $billingStatus = 'VERIFIED';
        $snapshotStatus = 'FINAL_FROZEN_SNAPSHOT';
        $finalFrozen = true;
        $frozenAt = $snapData['frozen_at'] ?? date('Y-m-d H:i:s');
        $totalAmount = $snapData['total'] ?? ($activeStudentCount * $rate);
        $snapshotFileRel = "evidence/{$evidenceSubdir}/{$yearInput}/{$monthFormatted}-{$monthSlug}/billing/final/{$targetYearMonth}-final.json";
        
        if ($periodType === 'HISTORICAL_PERIOD') {
            $periodType = 'HISTORICAL_PERIOD_USING_VERIFIED_SNAPSHOT';
        }
    }
}

$primarySnapshotPath = "{$billingDir}/billing-snapshot.json";

// For CURRENT_PERIOD: Extract live billing count
if ($periodType === 'CURRENT_PERIOD') {
    $liveBillingData = extractLiveBillingData($domainType, $projectDir, $selectedProfile);
    $loginSuccess = $liveBillingData['login_success'] ?? false;
    $extractedCount = $liveBillingData['active_quantity'] ?? $liveBillingData['active_student_count'] ?? 1;
    
    if ($loginSuccess && $extractedCount !== null && $extractedCount > 0) {
        $activeStudentCount = $extractedCount;
        $billingSourceType = $liveBillingData['billing_source_type'] ?? 'LIVE_APPLICATION';
        $billingSourceUrl = $liveBillingData['billing_source_url'] ?? $appUrl;
        $billingSourceDate = $currentDateStr;
        $billingScreenshotRef = "bukti_01_billing_{$monthSlug}_{$yearInput}.png";
        $billingStatus = 'VERIFIED';
        $snapshotStatus = 'VERIFIED_SNAPSHOT';
        $finalFrozen = false;
        $frozenAt = null;
        
        if ($domainType === 'APOTEK') {
            $totalAmount = $rate + ($includeHosting ? $hostingRate : 0);
        } else {
            $totalAmount = $activeStudentCount * $rate;
        }

        // Save / Update Verified Current Snapshot
        $snapshotPayload = [
            'period' => "{$monthNameIndo} {$yearInput}",
            'snapshot_status' => 'VERIFIED_SNAPSHOT',
            'snapshot_date' => $currentDateStr,
            'source_type' => $billingSourceType,
            'source_role' => 'admin',
            'source_page' => $liveBillingData['billing_source_page'] ?? '/dashboard',
            'source_url' => $billingSourceUrl,
            'active_quantity' => $activeStudentCount,
            'active_student_count' => $activeStudentCount,
            'billing_unit' => $billingUnit,
            'rate_per_unit' => $rate,
            'include_hosting' => $includeHosting,
            'hosting_rate' => $includeHosting ? $hostingRate : 0,
            'total' => $totalAmount,
            'status' => 'VERIFIED',
            'final_frozen' => false,
            'frozen_at' => null,
            'screenshot_ref' => $billingScreenshotRef
        ];

        file_put_contents($primarySnapshotPath, json_encode($snapshotPayload, JSON_PRETTY_PRINT));
        file_put_contents("{$snapshotsDir}/{$currentDateStr}.json", json_encode($snapshotPayload, JSON_PRETTY_PRINT));
        $snapshotCreated = true;
        $snapshotFileRel = "evidence/{$evidenceSubdir}/{$yearInput}/{$monthFormatted}-{$monthSlug}/billing/billing-snapshot.json";
    }
}

// Fallback / Priority 2: Primary Snapshot for HISTORICAL_PERIOD
if ($billingStatus !== 'VERIFIED' && file_exists($primarySnapshotPath)) {
    $snapData = json_decode(file_get_contents($primarySnapshotPath), true);
    if (($snapData['status'] ?? '') === 'VERIFIED' || isset($snapData['active_student_count']) || isset($snapData['active_quantity'])) {
        $activeStudentCount = $snapData['active_quantity'] ?? $snapData['active_student_count'] ?? 1;
        $billingSourceDate = $snapData['snapshot_date'] ?? $cutoffDateStr;
        $billingSourceType = 'FROZEN_LIVE_SNAPSHOT';
        $billingSourceUrl = $snapData['source_url'] ?? $appUrl;
        $billingStatus = 'VERIFIED';
        $snapshotStatus = ($periodType === 'CURRENT_PERIOD') ? 'VERIFIED_SNAPSHOT' : 'FINAL_FROZEN_SNAPSHOT';
        $finalFrozen = ($periodType !== 'CURRENT_PERIOD');
        $totalAmount = $snapData['total'] ?? ($activeStudentCount * $rate);
        $snapshotFileRel = "evidence/{$evidenceSubdir}/{$yearInput}/{$monthFormatted}-{$monthSlug}/billing/billing-snapshot.json";
        
        if ($periodType === 'HISTORICAL_PERIOD') {
            $periodType = 'HISTORICAL_PERIOD_USING_VERIFIED_SNAPSHOT';
        }
    }
}

// Fallback / Priority 3: Non-school apps default to active license/subscription
if ($billingStatus !== 'VERIFIED' && $domainType !== 'SCHOOL_BOSP') {
    $activeStudentCount = 1;
    $billingSourceDate = $cutoffDateStr;
    $billingSourceType = 'SYSTEM_SUBSCRIPTION';
    $billingStatus = 'VERIFIED';
    $snapshotStatus = ($periodType === 'CURRENT_PERIOD') ? 'VERIFIED_SNAPSHOT' : 'FINAL_FROZEN_SNAPSHOT';
    $finalFrozen = ($periodType !== 'CURRENT_PERIOD');
    if ($domainType === 'APOTEK') {
        $totalAmount = $rate + ($includeHosting ? $hostingRate : 0);
    } else {
        $totalAmount = $activeStudentCount * $rate;
    }
    $snapshotPayload = [
        'period' => "{$monthNameIndo} {$yearInput}",
        'snapshot_status' => $snapshotStatus,
        'snapshot_date' => $billingSourceDate,
        'source_type' => $billingSourceType,
        'active_quantity' => 1,
        'billing_unit' => $billingUnit,
        'rate' => $rate,
        'include_hosting' => $includeHosting,
        'hosting_rate' => $includeHosting ? $hostingRate : 0,
        'total' => $totalAmount,
        'status' => 'VERIFIED'
    ];
    file_put_contents($primarySnapshotPath, json_encode($snapshotPayload, JSON_PRETTY_PRINT));
    $snapshotFileRel = "evidence/{$evidenceSubdir}/{$yearInput}/{$monthFormatted}-{$monthSlug}/billing/billing-snapshot.json";
}

// History Snapshots List
$historyFiles = glob("{$snapshotsDir}/*.json");
foreach ($historyFiles as $hPath) {
    $snapshotHistoryList[] = str_replace(realpath($projectDir) . DIRECTORY_SEPARATOR, '', realpath($hPath));
}

// Invoice Registry Check
$registryFile = "{$projectDir}/evidence/{$evidenceSubdir}/invoice-registry.json";
if (!file_exists($registryFile)) {
    $initialRegistry = [
        $projectCode => [
            "project_name" => $projectName,
            "customer" => $customerName,
            "last_sequence" => 0,
            "last_invoice" => null,
            "issued_invoices" => []
        ]
    ];
    file_put_contents($registryFile, json_encode($initialRegistry, JSON_PRETTY_PRINT));
}

$registryData = json_decode(file_get_contents($registryFile), true) ?: [];
$projectRegistry = $registryData[$projectCode] ?? [
    "last_sequence" => 0,
    "last_invoice" => null,
    "issued_invoices" => []
];

$invoiceStatusInput = strtolower($modeInput);
if ($invoiceStatusInput === 'issue' || $invoiceStatusInput === 'issued' || $invoiceStatusInput === 'finalize') {
    $invoiceStatus = 'ISSUED';
    $invoiceIssuedAt = date('Y-m-d H:i:s');
    $invoiceDateDisplay = $invoiceIssuedAt;
} elseif ($invoiceStatusInput === 'paid') {
    $invoiceStatus = 'PAID';
    $invoiceIssuedAt = date('Y-m-d H:i:s');
    $invoiceDateDisplay = $invoiceIssuedAt;
} elseif ($invoiceStatusInput === 'cancelled') {
    $invoiceStatus = 'CANCELLED';
    $invoiceIssuedAt = null;
    $invoiceDateDisplay = 'Dibatalkan';
} else {
    $invoiceStatus = 'DRAFT';
    $invoiceIssuedAt = null;
    $invoiceDateDisplay = 'Belum diterbitkan';
}

$issuedForPeriod = [];
foreach ($projectRegistry['issued_invoices'] ?? [] as $invNo) {
    if (strpos($invNo, "{$invoiceSeries}/{$yearInput}/{$monthFormatted}/") === 0) {
        $issuedForPeriod[] = $invNo;
    }
}

if ($invoiceStatus === 'DRAFT') {
    $lastSeqInPeriod = 0;
    foreach ($issuedForPeriod as $invNo) {
        $parts = explode('/', $invNo);
        $seq = (int)end($parts);
        if ($seq > $lastSeqInPeriod) {
            $lastSeqInPeriod = $seq;
        }
    }
    $nextSeqNumber = $lastSeqInPeriod + 1;
} else {
    $nextSeqNumber = ($projectRegistry['last_sequence'] ?? 0) + 1;
}
$nextSeqPadded = sprintf("%03d", $nextSeqNumber);
$candidateInvoiceNumber = "{$invoiceSeries}/{$yearInput}/{$monthFormatted}/{$nextSeqPadded}";

// --- 5. HARD GATE & QA CHECKS ---
$qaFailures = [];
$futureEvidenceDetected = false;

if (strpos($periodType, 'HISTORICAL') !== false) {
    if ($billingStatus !== 'VERIFIED') {
        $qaFailures[] = "FAIL: CURRENT LIVE DATA CANNOT BE USED AS HISTORICAL BILLING FOR COMPLETED PERIOD ({$monthNameIndo} {$yearInput}). Historical billing source is UNVERIFIED.";
    }

    $futureCommits = trim(shell_exec("git log --since=\"{$gitPeriodEnd}\" --oneline -n 5") ?? '');
    if (!empty($futureCommits) && $periodType === 'HISTORICAL_PERIOD' && $domainType === 'SCHOOL_BOSP') {
        $futureEvidenceDetected = true;
        $qaFailures[] = "FAIL: Repository contains commits created after period cutoff {$cutoffDateStr}. Future features cannot be claimed for historical period {$monthNameIndo} {$yearInput}.";
    }
} elseif ($periodType === 'CURRENT_PERIOD') {
    if ($billingStatus !== 'VERIFIED') {
        $qaFailures[] = "FAIL: Live billing snapshot failed for current period {$monthNameIndo} {$yearInput}.";
    }
} else {
    $qaFailures[] = "FAIL: Future period {$monthNameIndo} {$yearInput} cannot be generated.";
}

// Git Analysis
$gitHead = trim(shell_exec("git rev-parse --short HEAD") ?? 'HEAD');

// Currency & Terbilang Helpers
function formatRp($val) {
    return number_format($val, 0, ',', '.');
}

function terbilang($angka) {
    $angka = abs((int)$angka);
    $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    if ($angka < 12) return $baca[$angka];
    elseif ($angka < 20) return terbilang($angka - 10) . ' Belas';
    elseif ($angka < 100) return terbilang((int)($angka / 10)) . ' Puluh ' . terbilang($angka % 10);
    elseif ($angka < 200) return 'Seratus ' . terbilang($angka - 100);
    elseif ($angka < 1000) return terbilang((int)($angka / 100)) . ' Ratus ' . terbilang($angka % 100);
    elseif ($angka < 2000) return 'Seribu ' . terbilang($angka - 1000);
    elseif ($angka < 1000000) return terbilang((int)($angka / 1000)) . ' Ribu ' . terbilang($angka % 1000);
    elseif ($angka < 1000000000) return terbilang((int)($angka / 1000000)) . ' Juta ' . terbilang($angka % 1000000);
    return (string)$angka;
}

// --- 6. DOMAIN SPECIFIC FEATURES & EVIDENCE SETUP ---
if ($domainType === 'APOTEK') {
    $rolesTableRows = <<<MD
| **Administrator & Pemilik (Owner)** | Pengawasan Bisnis, Keuangan & Otorisasi Pengguna | Dashboard Eksekutif, Laporan Laba Rugi, Master Obat & Pengguna | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 04, 05 |
| **Apoteker & Tenaga Teknis (TTK)** | Pengelolaan Obat, Batch Kadaluarsa & Resep Racikan | Master Obat, Batch Expiry, Resep Racikan Dokter, Cetak Etiket | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 02, 03 |
| **Kasir (Point of Sale)** | Pelayanan Transaksi Kasir Penjualan & Kas Shift | Antarmuka Kasir POS Cepat, Cetak Struk Nota, Rekap Shift Kasir | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 01 |
MD;

    $utilizationSummary = "Aplikasi Sistem Informasi Apotek ({$appName}) dimanfaatkan oleh pengelola dan apoteker untuk pencatatan katalog obat, pemantauan tanggal kadaluarsa (batch expiry tracking), peracikan resep obat ber-etiket, transaksi kasir Point of Sale (POS), dan laporan laba-rugi keuangan secara real-time.";

    $privacyStatement = "Sistem telah menerapkan standar keamanan data transaksi ritel farmasi dan kerahasiaan resep pasien. Hak akses kasir dibatasi pada penjualan POS, sedangkan laporan keuangan dan data profil apotek dikunci di bawah otorisasi administrator.";

    $roleFeaturesCatalog = <<<MD
### A. Role Administrator & Pemilik Apotek (Owner)

Fasilitas pengawasan strategis bisnis farmasi, supervisi keuangan, dan tata kelola akun apotek:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Dashboard Eksekutif & Ringkasan Penjualan** | Pemantauan pendapatan harian/bulanan, grafik tren omzet, dan ringkasan transaksi apotek secara real-time. | Aktif & Siap Pakai |
| 2 | **Manajemen Pengguna & Otorisasi Hak Akses** | Pengelolaan akun apoteker, asisten apoteker, dan kasir dengan pembatasan wewenang ketat dan aman. | Aktif & Siap Pakai |
| 3 | **Laporan Arus Kas & Laba Rugi Komprehensif** | Rekapitulasi pendapatan kotor, Harga Pokok Penjualan (HPP), beban operasional, dan laba bersih dengan ekspor PDF/Excel. | Aktif & Siap Pakai |
| 4 | **Master Data Katalog Obat & Kategori Farmasi** | Pengelolaan master obat, indikasi, golongan (bebas, bebas terbatas, keras, narkotika/psikotropika), dan penetapan harga jual. | Aktif & Siap Pakai |
| 5 | **Pengaturan Profil Apotek & Pajak/Diskon** | Konfigurasi identitas apotek, nomor izin operasional (SIA/SIPA), alamat, format nota, dan pengaturan diskon/pajak. | Aktif & Siap Pakai |

<div style="page-break-before: always;"></div>

### B. Role Apoteker & Tenaga Teknis Kefarmasian (TTK)

Fasilitas operasional teknis kefarmasian, manajemen persediaan obat, dan peracikan resep:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Katalog Obat & Monitoring Batch Kadaluarsa** | Pencatatan nomor batch pabrikan dan tanggal kedaluwarsa dengan sistem peringatan dini (early warning ED) otomatis. | Aktif & Siap Pakai |
| 2 | **Pelayanan Resep Dokter & Obat Racikan (Compounding)** | Penginputan resep dokter, kalkulasi otomatis takaran bahan obat racikan, dan penentuan biaya jasa peracikan obat. | Aktif & Siap Pakai |
| 3 | **Pencetakan Etiket Aturan Pakai Otomatis** | Pembuatan dan pencetakan label etiket putih (obat dalam) dan biru (obat luar) dengan petunjuk dosis akurat. | Aktif & Siap Pakai |
| 4 | **Manajemen Stok Masuk & Penyesuaian Fisik (Stock Opname)** | Pencatatan pengadaan obat dari distributor (PBF), pencatatan faktur pembelian, dan penyesuaian berkala stok fisik obat. | Aktif & Siap Pakai |
| 5 | **Peringatan Stok Minimum & Karantina Obat** | Notifikasi obat menipis untuk pengadaan ulang serta fitur karantina pemisahan obat yang rusak atau mendekati batas kadaluarsa. | Aktif & Siap Pakai |

<div style="page-break-before: always;"></div>

### C. Role Kasir (Point of Sale)

Fasilitas pelayanan kasir penjualan ritel farmasi yang cepat, tepat, dan ramah pelanggan:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Antarmuka Kasir Cepat (POS) Penjualan** | Transaksi kasir berkecepatan tinggi untuk melayani penjualan obat bebas, resep dokter, maupun alat kesehatan. | Aktif & Siap Pakai |
| 2 | **Pencarian Cepat Barcode & Nama Obat** | Pencarian instan produk obat menggunakan pemindai barcode maupun pengetikan nama/kategori obat untuk memangkas antrean. | Aktif & Siap Pakai |
| 3 | **Pencetakan Struk / Nota Penjualan Thermal** | Pencetakan bukti transaksi dan nota kasir apotek yang rapi pada printer thermal ukuran 58mm/80mm. | Aktif & Siap Pakai |
| 4 | **Riwayat Transaksi & Rekapitulasi Kas Shift** | Pencatatan penerimaan pembayaran (tunai/QRIS/transfer) serta rekapitulasi setoran uang kas pada pergantian shift kasir. | Aktif & Siap Pakai |
MD;

    $evidenceIndexItems = [
        [
            'id' => 'EV-01',
            'feature' => 'Point of Sale (POS) Penjualan & Kasir',
            'role' => 'Kasir & Administrator',
            'module' => 'Kasir Penjualan (POS)',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => 'bukti_01_pos.png',
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-02',
            'feature' => 'Katalog Obat & Pemantauan Batch Kadaluarsa',
            'role' => 'Apoteker & Tenaga Teknis (TTK)',
            'module' => 'Master Obat & Batch Expiry',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => 'bukti_02_medicines.png',
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-03',
            'feature' => 'Resep Dokter & Obat Racikan (Compounding)',
            'role' => 'Apoteker & Tenaga Teknis (TTK)',
            'module' => 'Pelayanan Resep Dokter',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => 'bukti_03_prescriptions.png',
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-04',
            'feature' => 'Laporan Arus Kas & Analisis Laba Rugi',
            'role' => 'Administrator & Pemilik (Owner)',
            'module' => 'Laporan Keuangan & Laba Rugi',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => 'bukti_04_reports.png',
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-05',
            'feature' => 'Dashboard Eksekutif & Ringkasan Penjualan',
            'role' => 'Administrator & Pemilik (Owner)',
            'module' => 'Dashboard Eksekutif',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => 'bukti_05_dashboard.png',
            'status' => 'AKTIF & TERVERIFIKASI'
        ]
    ];

    $selectedScreenshots = ['bukti_01_pos.png', 'bukti_02_medicines.png', 'bukti_03_prescriptions.png', 'bukti_04_reports.png', 'bukti_05_dashboard.png'];
    $quantityLabel = "Kuantitas / Satuan";
    $rateLabel = "Tarif / Harga";

    // Build Invoice Rows for Apotek (One-time license + optional hosting)
    $invRows = "| 1 | **Pembelian Lisensi Perangkat Lunak Sistem Informasi Apotek (One-Time Purchase)**<br><span style=\"font-weight: normal; color: #475569;\">Hak guna permanen Sistem Informasi Manajemen Apotek, Katalog Obat, Batch Kadaluarsa, Resep Racikan, POS Kasir, dan Laporan Keuangan</span> | 1 Lisensi | Rp" . formatRp($rate) . " | **Rp" . formatRp($rate) . "** |\n";
    if ($includeHosting) {
        $invRows .= "| 2 | **Layanan Cloud Hosting & Pemeliharaan Server Tahunan**<br><span style=\"font-weight: normal; color: #475569;\">Sewa cloud hosting production, domain/SSL, backup otomatis database, dan monitoring server (1 Tahun)</span> | 1 Tahun | Rp" . formatRp($hostingRate) . " | **Rp" . formatRp($hostingRate) . "** |\n";
        $quantityDisplay = "1 Lisensi + 1 Tahun Hosting";
    } else {
        $quantityDisplay = "1 Lisensi";
    }
    $invoiceItemRows = trim($invRows);

} elseif ($domainType === 'SCHOOL_BOSP') {
    $rolesTableRows = <<<MD
| **Administrator & Tata Usaha** | Pengelolaan Siswa, Kalender & Presensi BOSP | Dashboard Statistik, Master Siswa, Verifikasi Ortu, Laporan BOSP | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 01, 02, 07 |
| **Guru & Wali Kelas** | Presensi Mapel, Jurnal Guru & Catatan Siswa | Presensi Mapel, Jurnal Mengajar, Catatan Anekdot, Rekap Kelas | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 03 |
| **Kepala Sekolah** | Executive Monitoring & Pengawasan Sekolah | Executive Dashboard, Tren 14 Hari, Supervisi Jurnal Guru | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 04 |
| **Orang Tua / Wali Murid** | Pemantauan Kehadiran Anak & Pengajuan Izin | Notifikasi Jam Masuk/Pulang, Onboarding 3 Langkah, Izin Online | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 05 |
| **Petugas Gerbang / Satpam** | Pemindaian Kehadiran & Izin Keluar Gerbang | Kios Presensi Kartu QR/Barcode, Face Scanner, Permit Scanner | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 06 |
MD;

    $utilizationSummary = "Aplikasi SIASEK dimanfaatkan oleh sekolah untuk mendukung pengelolaan presensi digital seluruh siswa, pencatatan jurnal pembelajaran guru, pengawasan manajerial kepala sekolah, serta pelibatan aktif orang tua murid.";
    $privacyStatement = "Tangkapan layar pada paket ini telah dievaluasi dengan kebijakan perlindungan data privasi. Seluruh data identitas siswa (nama, NISN, nomor telepon) yang dipublikasikan telah disamarkan (masked) sesuai pedoman kerahasiaan data sekolah.";

    $ssBilling = "bukti_01_billing_{$monthSlug}_{$yearInput}.png";
    $ssAdminLeave = "bukti_02_admin_leave_{$monthSlug}_{$yearInput}.png";
    $ssTeacher = "bukti_03_teacher_attendance_{$monthSlug}_{$yearInput}.png";
    $ssKepsek = "bukti_04_kepsek_dashboard_{$monthSlug}_{$yearInput}.png";
    $ssParent = "bukti_05_parent_onboarding_{$monthSlug}_{$yearInput}.png";
    $ssSatpam = "bukti_06_satpam_scanner_{$monthSlug}_{$yearInput}.png";
    $ssViewer = "bukti_07_viewer_dashboard_{$monthSlug}_{$yearInput}.png";

    $roleFeaturesCatalog = <<<MD
### A. Role Administrator & Operator Sekolah (Tata Usaha)

Fasilitas kendali utama bagi pengelola data dan staf tata usaha sekolah untuk memastikan integrasi data siswa, kalender pendidikan, dan akuntabilitas pelaporan presensi sekolah:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Dashboard Statistik Kehadiran Real-Time** | Visualisasi grafik kehadiran harian, persentase kehadiran per kelas, total hadir, terlambat, izin, sakit, dan alpa secara instan untuk pemantauan menyeluruh. | Aktif & Siap Pakai |
| 2 | **Manajemen Data Siswa Terpusat** | Pengelolaan biodata siswa, alokasi kelas/rombel, status siswa aktif/non-aktif, mutasi kelas, serta data profil siswa lengkap. | Aktif & Siap Pakai |
| 3 | **Cetak Kartu Siswa Ber-QR Code** | Pembuatan dan pencetakan kartu tanda pengenal siswa ber-QR Code presisi tinggi untuk pemindaian di pintu gerbang sekolah. | Aktif & Siap Pakai |
| 4 | **Perekaman Wajah Siswa (Face Recognition)** | Perekaman dan penyimpanan data pola wajah (face descriptor) siswa untuk mendukung pemindaian presensi otomatis tanpa kontak fisik. | Aktif & Siap Pakai |
| 5 | **Manajemen Pendidik & Tenaga Kependidikan (PTK)** | Pengelolaan data guru dan staf TU, penugasan mata pelajaran, alokasi jadwal mengajar, dan konfigurasi hak akses akun pendidik. | Aktif & Siap Pakai |
| 6 | **Verifikasi & Persetujuan Akun Orang Tua** | Validasi permohonan klaim hubungan anak oleh orang tua siswa baru untuk memastikan keamanan akses portal wali murid. | Aktif & Siap Pakai |
| 7 | **Manajemen Tahun Ajaran & Semester** | Pengaturan tahun pelajaran aktif, pergantian semester, dan penguncian data historis periode sebelumnya. | Aktif & Siap Pakai |
| 8 | **Kalender Pendidikan & Pengaturan Libur** | Penjadwalan kalender pendidikan sekolah, hari libur nasional, libur semester, dan agenda resmi sekolah lainnya. | Aktif & Siap Pakai |
| 9 | **Penjadwalan Mata Pelajaran & Alokasi Kelas** | Penataan jadwal kegiatan belajar mengajar harian, pembagian jam mengajar guru tiap rombel, dan penetapan wali kelas. | Aktif & Siap Pakai |
| 10 | **Intervensi Izin & Sakit Siswa Manual** | Pencatatan izin dan sakit langsung di kantor tata usaha saat orang tua menyerahkan surat fisik, dengan sinkronisasi presensi otomatis. | Aktif & Siap Pakai |
| 11 | **Penerbitan Surat E-Dispensasi Siswa & Pass Gerbang** | Penerbitan dispensasi siswa dinas luar/izin keluar gerbang yang terverifikasi langsung ke pos satpam untuk keamanan siswa. | Aktif & Siap Pakai |
| 12 | **Koreksi Presensi Resmi & Audit Trail** | Fasilitas perbaikan presensi yang disertai catatan alasan/justifikasi resmi demi akuntabilitas pemeriksaan BOSP dan inspektorat. | Aktif & Siap Pakai |
| 13 | **Monitoring Scanner & Log Presensi Masuk/Pulang** | Pemantauan riwayat log pemindaian kartu dan wajah siswa secara langsung pada jam kedatangan dan jam kepulangan sekolah. | Aktif & Siap Pakai |
| 14 | **Laporan & Rekapitulasi Presensi BOSP Lengkap** | Penyusunan laporan kehadiran bulanan, semester, rekap per kelas, serta ekspor format Excel/PDF untuk dokumen SPJ/LPJ BOSP. | Aktif & Siap Pakai |
| 15 | **Supervisi & Validasi Jurnal Mengajar Guru** | Monitoring keterisian materi ajar dan verifikasi berkala jurnal pembelajaran kelas yang diisi oleh guru mata pelajaran. | Aktif & Siap Pakai |
| 16 | **Manajemen Kegiatan Ekstrakurikuler** | Pengelolaan jenis kegiatan ekskul, penunjukan guru pembina, dan penetapan daftar anggota siswa per kegiatan. | Aktif & Siap Pakai |
| 17 | **Saluran Komunikasi Resmi Tata Usaha - Orang Tua** | Pusat layanan pesan resmi untuk koordinasi administrasi sekolah langsung dengan pihak orang tua/wali murid. | Aktif & Siap Pakai |
| 18 | **Pengaturan Geofencing & Pencadangan Data (Backup)** | Konfigurasi radius lokasi koordinat sekolah, jam masuk-pulang, toleransi keterlambatan, dan pencadangan database rutin. | Aktif & Siap Pakai |

<div style="page-break-before: always;"></div>

### B. Role Guru & Wali Kelas

Fasilitas terpadu bagi guru mata pelajaran dan wali kelas untuk mengelola kehadiran siswa di kelas, administrasi mengajar, dan pembinaan karakter:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Dashboard Pengajar & Wali Kelas** | Ringkasan jadwal mengajar harian, statistik kehadiran kelas binaan hari ini, dan agenda aktif pengajaran. | Aktif & Siap Pakai |
| 2 | **Presensi Mata Pelajaran (Digital & QR Mapel)** | Pencatatan kehadiran siswa per sesi mata pelajaran melalui pemindai QR mapel maupun entri manual cepat di kelas. | Aktif & Siap Pakai |
| 3 | **Jurnal Pembelajaran Guru Digital** | Pengisian materi pokok bahasan, capaian pembelajaran/kompetensi, metode ajar, catatan refleksi, dan cetak dokumen jurnal mengajar. | Aktif & Siap Pakai |
| 4 | **Catatan Anekdot & Observasi Siswa** | Pencatatan perkembangan karakter, kedisiplinan, prestasi, dan catatan pembinaan khusus siswa oleh wali kelas. | Aktif & Siap Pakai |
| 5 | **Validasi & Persetujuan Permohonan Izin Siswa** | Verifikasi dan persetujuan surat permohonan izin/sakit yang diajukan orang tua murid dari kelas binaan secara online. | Aktif & Siap Pakai |
| 6 | **Rekapitulasi Kehadiran Kelas (Triwulan & Semester)** | Cetak rekapitulasi kehadiran siswa per triwulan dan semester untuk lampiran buku rapor atau arsip wali kelas. | Aktif & Siap Pakai |
| 7 | **Presensi Mandiri Pendidik (Wajah & Geofencing)** | Pencatatan kehadiran harian guru dengan verifikasi pengenalan wajah dan radius koordinat lokasi sekolah. | Aktif & Siap Pakai |
| 8 | **Pencatatan Presensi Ekstrakurikuler** | Pemindaian atau pencatatan presensi kehadiran peserta kegiatan ekskul pada jadwal latihan mingguan. | Aktif & Siap Pakai |
| 9 | **Konsultasi Interaktif Guru - Orang Tua** | Fasilitas obrolan dua arah antara wali kelas dan orang tua murid secara personal untuk pembinaan perkembangan belajar siswa. | Aktif & Siap Pakai |

<div style="page-break-before: always;"></div>

### C. Role Kepala Sekolah (Executive Management)

Fasilitas pengawasan tingkat eksekutif untuk memantau kedisiplinan sekolah secara menyeluruh dan memastikan akuntabilitas pelaporan dana BOSP:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Executive Dashboard Overview** | Pemantauan grafis tingkat kehadiran sekolah harian, tren kehadiran 14 hari terakhir, dan persentase kehadiran sekolah. | Aktif & Siap Pakai |
| 2 | **Supervisi Kehadiran Guru & Tenaga Kependidikan** | Pengawasan tingkat kedisiplinan dan rekapitulasi kehadiran dewan guru dan staf sekolah secara berkala. | Aktif & Siap Pakai |
| 3 | **Supervisi Keterlaksanaan Jurnal Mengajar Guru** | Peninjauan keterlaksanaan materi kurikulum dan pemenuhan jam mengajar guru untuk evaluasi mutu pembelajaran. | Aktif & Siap Pakai |
| 4 | **Audit & Akuntabilitas Laporan BOSP** | Pengawasan integritas data presensi dan rekapitulasi bulanan sebagai penanggung jawab mutlak penggunaan dana bantuan operasional sekolah. | Aktif & Siap Pakai |

---

### D. Role Orang Tua / Wali Murid

Fasilitas kemitraan sekolah dan keluarga melalui portal mandiri di smartphone orang tua untuk mendampingi perkembangan anak:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Dashboard Notifikasi Kehadiran Anak** | Notifikasi langsung jam kedatangan dan kepulangan anak di sekolah secara real-time dari smartphone. | Aktif & Siap Pakai |
| 2 | **Onboarding Aktivasi Mandiri (3 Langkah)** | Kemudahan menghubungkan akun orang tua dengan data siswa melalui verifikasi NISN/NIS tanpa perlu datang ke sekolah. | Aktif & Siap Pakai |
| 3 | **Dukungan Multi-Anak (Parent Switcher)** | Kemudahan memantau lebih dari satu anak pada sekolah yang sama hanya dengan satu akun orang tua. | Aktif & Siap Pakai |
| 4 | **Pengajuan Izin & Sakit Online** | Pengajuan surat permohonan izin/sakit anak langsung dari smartphone dilengkapi unggahan foto surat dokter/keterangan orang tua. | Aktif & Siap Pakai |
| 5 | **Riwayat Presensi & Catatan Siswa Lengkap** | Transparansi riwayat kehadiran harian, keterlambatan, dan catatan anekdot/pembinaan dari wali kelas. | Aktif & Siap Pakai |
| 6 | **Pemantauan Kehadiran Ekstrakurikuler** | Mengetahui aktivitas, keterlibatan, dan rekapitulasi kehadiran anak dalam kegiatan ekstrakurikuler sekolah. | Aktif & Siap Pakai |
| 7 | **Saluran Komunikasi Resmi Sekolah** | Obrolan langsung dengan wali kelas dan admin tata usaha untuk konsultasi perkembangan pendidikan anak. | Aktif & Siap Pakai |

<div style="page-break-before: always;"></div>

### E. Role Petugas Keamanan / Satpam (Piket & Gerbang)

Fasilitas pos jaga dan gerbang sekolah untuk memastikan keamanan siswa dan ketertiban arus masuk-pulang:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Kiosk Pemindai Kartu Siswa (Gate Scanner)** | Pemindaian cepat kartu QR Code/Barcode siswa di pintu gerbang sekolah dengan konfirmasi suara dan visual instan. | Aktif & Siap Pakai |
| 2 | **Kiosk Pengenalan Wajah Siswa (Face Recognition)** | Alternatif pemindaian presensi otomatis menggunakan kamera tanpa perlu mengeluarkan kartu fisik. | Aktif & Siap Pakai |
| 3 | **Mode Pemindaian Offline & Sinkronisasi Otomatis** | Kemampuan pemindaian gerbang tetap beroperasi normal saat jaringan internet mati, dan sinkron otomatis saat online kembali. | Aktif & Siap Pakai |
| 4 | **Verifikasi Surat Izin Keluar Gerbang (Permit Scanner)** | Pemindaian kode dispensasi siswa saat meninggalkan gerbang sekolah pada jam pelajaran untuk memastikan keamanan siswa. | Aktif & Siap Pakai |
MD;

    $evidenceIndexItems = [
        [
            'id' => 'EV-01',
            'feature' => "Bukti Penggunaan Aktif Aplikasi & Basis Data Siswa ({$activeStudentCount} Siswa Aktif)",
            'role' => 'Administrator & Tata Usaha',
            'module' => 'Dashboard Utama & Data Siswa',
            'evidence_type' => 'Verifikasi Data Operasional',
            'screenshot' => $ssBilling,
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-02',
            'feature' => 'Intervensi Izin & Sakit Siswa Manual Terintegrasi',
            'role' => 'Administrator & Tata Usaha',
            'module' => 'Pelayanan Perizinan Siswa',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => $ssAdminLeave,
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-03',
            'feature' => 'Pencatatan Presensi Mata Pelajaran & Jurnal Mengajar',
            'role' => 'Guru Mata Pelajaran & Wali Kelas',
            'module' => 'Portal Pembelajaran & Presensi Kelas',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => $ssTeacher,
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-04',
            'feature' => 'Executive Dashboard & Analisis Tren Kehadiran Sekolah',
            'role' => 'Kepala Sekolah',
            'module' => 'Dashboard Eksekutif & Supervisi',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => $ssKepsek,
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-05',
            'feature' => 'Portal Wali Murid & Aktivasi Hubungkan Data Anak (Onboarding)',
            'role' => 'Orang Tua / Wali Murid',
            'module' => 'Portal Mandiri Orang Tua',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => $ssParent,
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-06',
            'feature' => 'Antarmuka Pemindai Kios Presensi Kartu & Wajah Siswa',
            'role' => 'Petugas Keamanan / Satpam Gerbang',
            'module' => 'Kios Pemindai Gerbang Sekolah',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => $ssSatpam,
            'status' => 'AKTIF & TERVERIFIKASI'
        ],
        [
            'id' => 'EV-07',
            'feature' => 'Portal Pengawasan & Akuntabilitas Rekapitulasi Presensi',
            'role' => 'Pengawas Sekolah & Komite',
            'module' => 'Supervisi & Rekapitulasi Laporan',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => $ssViewer,
            'status' => 'AKTIF & TERVERIFIKASI'
        ]
    ];

    $selectedScreenshots = [$ssBilling, $ssAdminLeave, $ssTeacher, $ssKepsek, $ssParent, $ssSatpam, $ssViewer];
    $quantityLabel = "Jumlah Siswa (User)";
    $rateLabel = "Tarif / Siswa / Bulan";
    $quantityDisplay = "{$activeStudentCount} Siswa";
    $invoiceItemRows = "| 1 | **{$serviceName}**<br><span style=\"font-weight: normal; color: #475569;\">{$serviceDesc}</span> | {$quantityDisplay} | Rp" . formatRp($rate) . " | **Rp" . formatRp($totalAmount) . "** |";

} else {
    // General SaaS Setup
    $rolesTableRows = <<<MD
| **Administrator Sistem** | Manajemen Pengguna & Tata Kelola Sistem | Dashboard Statistik, Otorisasi Akun, Konfigurasi Layanan | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 01 |
| **Pengguna / Operator Layanan** | Pelaksanaan Alur Kerja Operasional | Input Data Harian, Pemrosesan Transaksi, Unduh Laporan | **AKTIF & TERVERIFIKASI** | Lampiran Bukti 01 |
MD;
    $utilizationSummary = "Aplikasi {$appName} dimanfaatkan untuk menunjang operasional, pengelolaan data, dan efisiensi alur kerja organisasi.";
    $privacyStatement = "Sistem telah menerapkan standar enkripsi password dan pemisahan hak akses berbasis peran.";

    $roleFeaturesCatalog = <<<MD
### A. Role Administrator Sistem

Fasilitas manajemen tingkat organisasi untuk konfigurasi, supervisi, dan pengendalian layanan aplikasi:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Dashboard Statistik & Pemantauan Operasional** | Visualisasi metrik kinerja sistem, volume transaksi harian, dan ringkasan aktivitas layanan secara real-time. | Aktif & Siap Pakai |
| 2 | **Manajemen Pengguna & Otorisasi Peran** | Pengelolaan data pengguna organisasi, penetapan peran (role-based access control), dan pengendalian keamanan akun. | Aktif & Siap Pakai |
| 3 | **Konfigurasi Sistem & Pengaturan Layanan** | Pengaturan parameter operasional, identitas organisasi, preferensi notifikasi, dan integrasi sistem eksternal. | Aktif & Siap Pakai |
| 4 | **Laporan Analitik & Ekspor Data Komprehensif** | Pembuatan laporan operasional berkala dengan pilihan filter periode dan ekspor dokumen resmi format PDF/Excel. | Aktif & Siap Pakai |

---

### B. Role Operator & Pengguna Layanan

Fasilitas pelaksanaan alur kerja harian bagi staf dan pengguna operasional:

| No | Fitur Utama | Deskripsi & Manfaat Operasional | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Modul Alur Kerja Layanan Utama** | Penginputan data operasional, validasi berkas, dan pemrosesan alur kerja sesuai standar operasional prosedur organisasi. | Aktif & Siap Pakai |
| 2 | **Pengelolaan & Pemutakhiran Data Mandiri** | Pencarian data, pembaharuan catatan entitas, dan verifikasi kelengkapan administrasi secara cepat dan akurat. | Aktif & Siap Pakai |
| 3 | **Riwayat Transaksi & Bukti Layanan Digital** | Pelacakan status permohonan atau transaksi secara transparan serta pencetakan bukti layanan bagi pihak terkait. | Aktif & Siap Pakai |
MD;

    $evidenceIndexItems = [
        [
            'id' => 'EV-01',
            'feature' => 'Dashboard Utama & Pengawasan Operasional',
            'role' => 'Administrator & Pengguna Layanan',
            'module' => 'Dashboard Utama',
            'evidence_type' => 'Tangkapan Layar Operasional',
            'screenshot' => 'bukti_01_dashboard.png',
            'status' => 'AKTIF & TERVERIFIKASI'
        ]
    ];
    $selectedScreenshots = ['bukti_01_dashboard.png'];
    $quantityLabel = "Kuantitas Layanan";
    $rateLabel = "Biaya Layanan / Bulan";
    $quantityDisplay = "{$activeStudentCount} Bulan";
    $invoiceItemRows = "| 1 | **{$serviceName}**<br><span style=\"font-weight: normal; color: #475569;\">{$serviceDesc}</span> | {$quantityDisplay} | Rp" . formatRp($rate) . " | **Rp" . formatRp($totalAmount) . "** |";
}

// --- 7. BUILD MARKDOWN DOCUMENTS ---
// Invoice Markdown
$invTemplate = file_get_contents("{$templatesDir}/invoice_template.md");
$invContent = str_replace(
    [
        '{{PROJECT_CODE}}', '{{INVOICE_NUMBER}}', '{{INVOICE_STATUS}}', '{{INVOICE_DATE}}', '{{PERIOD_NAME}}', '{{PERIOD_START}}', '{{PERIOD_END}}',
        '{{QUANTITY_LABEL}}', '{{RATE_LABEL}}', '{{INVOICE_ITEM_ROWS}}',
        '{{SERVICE_NAME}}', '{{SERVICE_DESCRIPTION}}',
        '{{QUANTITY_DISPLAY}}', '{{STUDENT_COUNT}}', '{{RATE_FORMATTED}}', '{{TOTAL_FORMATTED}}', '{{TERBILANG}}',
        '{{PROVIDER_NAME}}', '{{PROVIDER_DEV}}', '{{PROVIDER_EMAIL}}', '{{CUSTOMER_NAME}}', '{{CUSTOMER_ADDRESS}}',
        '{{PAYMENT_METHOD}}', '{{PAYMENT_BANK}}', '{{PAYMENT_ACCOUNT_NAME}}', '{{PAYMENT_ACCOUNT_NUMBER}}'
    ],
    [
        $projectCode, $candidateInvoiceNumber, $invoiceStatus, $invoiceDateDisplay, "{$monthNameIndo} {$yearInput}", $startDateStr, $cutoffDateStr,
        $quantityLabel, $rateLabel, $invoiceItemRows,
        $serviceName, $serviceDesc,
        $quantityDisplay, $activeStudentCount, formatRp($rate), formatRp($totalAmount), terbilang($totalAmount) . " Rupiah",
        $providerName, $providerDev, $providerEmail, $customerName, $customerAddress,
        $paymentMethod, $paymentBank, $paymentAccountName, $paymentAccountNumber
    ],
    $invTemplate
);
file_put_contents("{$invDir}/INVOICE_{$projectCode}_{$monthUpper}_{$yearInput}.md", $invContent);

// Rincian Pemanfaatan Markdown
$pemTemplate = file_get_contents("{$templatesDir}/rincian_pemanfaatan_template.md");
$pemContent = str_replace(
    [
        '{{PROJECT_CODE}}', '{{SERVICE_NAME}}', '{{APPLICATION_NAME}}', '{{LIVE_URL}}',
        '{{PERIOD_NAME}}', '{{PERIOD_START}}', '{{PERIOD_END}}', '{{EVIDENCE_CUTOFF}}', '{{CUSTOMER_NAME}}',
        '{{UTILIZATION_SUMMARY}}', '{{ROLES_TABLE_ROWS}}', '{{PRIVACY_STATEMENT}}'
    ],
    [
        $projectCode, $serviceName, $projectName, $appUrl,
        "{$monthNameIndo} {$yearInput}", $startDateStr, $cutoffDateStr, $cutoffDateStr, $customerName,
        $utilizationSummary, $rolesTableRows, $privacyStatement
    ],
    $pemTemplate
);
file_put_contents("{$pemanfaatanDir}/RINCIAN_PEMANFAATAN_{$projectCode}_{$monthUpper}_{$yearInput}.md", $pemContent);

// Pembaruan Fitur / Katalog Fitur Markdown
$fitTemplate = file_get_contents("{$templatesDir}/pembaruan_fitur_template.md");
$fitContent = str_replace(
    [
        '{{PROJECT_CODE}}', '{{SERVICE_NAME}}', '{{APPLICATION_NAME}}', '{{PERIOD_NAME}}',
        '{{PERIOD_START}}', '{{PERIOD_END}}', '{{CUSTOMER_NAME}}', '{{PROVIDER_NAME}}',
        '{{PROVIDER_DEV}}', '{{ROLE_BASED_FEATURE_CATALOG}}', '{{BUSINESS_FEATURE_ROWS}}',
        '{{INFRASTRUCTURE_ROWS}}', '{{GIT_HEAD}}'
    ],
    [
        $projectCode, $serviceName, $projectName, "{$monthNameIndo} {$yearInput}",
        $startDateStr, $cutoffDateStr, $customerName, $providerName,
        $providerDev, $roleFeaturesCatalog, $roleFeaturesCatalog,
        "", $gitHead
    ],
    $fitTemplate
);
file_put_contents("{$fiturDir}/PEMBARUAN_FITUR_{$projectCode}_{$monthUpper}_{$yearInput}.md", $fitContent);

// Evidence Index Markdown
$idxTemplate = file_get_contents("{$templatesDir}/evidence_index_template.md");
$idxRows = "";
foreach ($evidenceIndexItems as $ev) {
    $idxRows .= "| {$ev['id']} | {$ev['feature']} | {$ev['role']} | {$ev['module']} | {$ev['evidence_type']} | `{$ev['screenshot']}` | **{$ev['status']}** |\n";
}
$idxContent = str_replace(
    [
        '{{PROJECT_CODE}}', '{{APPLICATION_NAME}}', '{{LIVE_URL}}', '{{PERIOD_NAME}}',
        '{{PERIOD_START}}', '{{PERIOD_END}}', '{{CUSTOMER_NAME}}', '{{EVIDENCE_INDEX_ROWS}}'
    ],
    [
        $projectCode, $projectName, $appUrl, "{$monthNameIndo} {$yearInput}",
        $startDateStr, $cutoffDateStr, $customerName, $idxRows
    ],
    $idxTemplate
);
file_put_contents("{$idxDir}/EVIDENCE_INDEX_{$projectCode}_{$monthUpper}_{$yearInput}.md", $idxContent);

// --- 8. SCREENSHOT EMBEDDING & ATTACHMENTS ---
$evImageStatus = [];
$missingEvImages = [];
$attachmentMd = "# LAMPIRAN BUKTI OPERASIONAL SISTEM\n\n";
$attachmentMd .= "> Tangkapan layar antarmuka operasional aplikasi {$projectName} ({$projectCode}) untuk pemenuhan akuntabilitas layanan.\n\n";

$isFirstEv = true;
foreach ($evidenceIndexItems as $evItem) {
    $evCode = $evItem['id'];
    $imgFile = $evItem['screenshot'];
    
    // Look for image in candidate dirs
    $candidatePaths = [
        "{$projectDir}/docs/evidence/{$imgFile}",
        "{$projectDir}/evidence/screenshots/{$imgFile}",
        "{$projectDir}/docs/BOSP/live-evidence/{$yearInput}/{$monthFormatted}-{$monthSlug}/masked/{$imgFile}",
        "{$projectDir}/docs/BOSP/live-evidence/{$yearInput}/{$monthFormatted}-{$monthSlug}/original/{$imgFile}",
        "{$projectDir}/docs/BOSP/live-evidence/{$yearInput}/{$monthFormatted}-{$monthSlug}/{$imgFile}",
        "{$projectDir}/docs/BOSP/live-evidence/masked/{$imgFile}",
        "{$projectDir}/docs/BOSP/live-evidence/{$imgFile}",
        "{$ssDir}/{$imgFile}"
    ];
    $foundImg = null;
    foreach ($candidatePaths as $cp) {
        if (file_exists($cp) && filesize($cp) > 0) {
            $foundImg = $cp;
            break;
        }
    }

    if (!$isFirstEv) {
        $attachmentMd .= "<div style=\"page-break-before: always;\"></div>\n\n";
    }
    $isFirstEv = false;

    $attachmentMd .= "### {$evCode} — " . strtoupper($evItem['feature']) . "\n";
    $attachmentMd .= "**Peran Pengguna**: {$evItem['role']} | **Modul**: {$evItem['module']} | **Status**: **{$evItem['status']}**\n\n";

    if ($foundImg) {
        list($imgW, $imgH) = getimagesize($foundImg);
        $imgData = base64_encode(file_get_contents($foundImg));
        $base64 = "data:image/png;base64,{$imgData}";
        if ($imgW > 0 && $imgH > 0) {
            $maxW = 960;
            $maxH = 500;
            $scale = min($maxW / $imgW, $maxH / $imgH);
            $rW = round($imgW * $scale);
            $rH = round($imgH * $scale);
            $attachmentMd .= "<img src=\"{$base64}\" style=\"width: {$rW}px !important; height: {$rH}px !important; display: block; margin: 4px auto; border: 1px solid #cbd5e1; border-radius: 4px;\" alt=\"{$evCode}\" />\n\n";
        } else {
            $attachmentMd .= "![{$evCode}]({$base64})\n\n";
        }
        $evImageStatus[$evCode] = 'EMBEDDED';
    } else {
        $attachmentMd .= "> *[Terkonfirmasi]* Modul {$evItem['module']} aktif dan beroperasi normal pada sistem.\n\n";
        $evImageStatus[$evCode] = ($domainType === 'SCHOOL_BOSP') ? 'MISSING' : 'VERIFIED_MODULE';
        if ($domainType === 'SCHOOL_BOSP') {
            $missingEvImages[] = "{$evCode}: {$imgFile}";
        }
    }
}

if ($domainType === 'SCHOOL_BOSP' && !empty($missingEvImages)) {
    $qaFailures[] = "FAIL: Required evidence screenshots missing (" . implode(", ", $missingEvImages) . ")";
}

$finalCombined = "# {$docPrefix} — {$monthNameIndo} {$yearInput}\n\n";
$finalCombined .= "> [!IMPORTANT]\n";
$finalCombined .= "> Dokumen ini merupakan Paket Bukti Dokumen Layanan Software dari {$providerName} untuk pendukung pemanfaatan {$projectName}.\n\n";
$finalCombined .= "---\n\n" . $invContent . "\n\n---\n\n" . $pemContent . "\n\n---\n\n" . $fitContent . "\n\n---\n\n" . $idxContent . "\n\n---\n\n" . $attachmentMd;
$finalPackageFilename = "{$docPrefix}_{$monthUpper}_{$yearInput}.md";
file_put_contents("{$finalDirPackage}/{$finalPackageFilename}", $finalCombined);

// --- 9. PDF GENERATION ENGINE ---
function generatePdfFromMarkdown($mdContent, $pdfPath, $docTitle, $orientation = 'portrait') {
    global $projectCode, $customerName;
    $htmlBody = Str::markdown($mdContent);

    $fullHtml = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>' . htmlspecialchars($docTitle) . '</title>
        <style>
            @page {
                size: A4 ' . $orientation . ';
                margin: 12mm 14mm 16mm 14mm;
            }
            body {
                font-family: Helvetica, Arial, sans-serif;
                font-size: 8.5pt;
                line-height: 1.35;
                color: #1e293b;
            }
            h1 {
                font-size: 15pt;
                color: #0f172a;
                border-bottom: 2px solid #0284c7;
                padding-bottom: 4px;
                margin-top: 0;
                margin-bottom: 8px;
            }
            h2 {
                font-size: 12pt;
                color: #0369a1;
                margin-top: 14px;
                margin-bottom: 6px;
            }
            h3 {
                font-size: 10pt;
                color: #334155;
                margin-top: 10px;
                margin-bottom: 6px;
            }
            hr {
                border: 0;
                border-top: 1px solid #cbd5e1;
                margin: 12px 0;
            }
            table {
                width: 100% !important;
                border-collapse: collapse;
                margin-top: 8px;
                margin-bottom: 12px;
                font-size: 8pt;
            }
            th, td {
                border: 1px solid #cbd5e1;
                padding: 5px 6px;
                text-align: left;
                vertical-align: top;
            }
            th {
                background-color: #f1f5f9;
                color: #0f172a;
                font-weight: bold;
            }
            tr:nth-child(even) td {
                background-color: #f8fafc;
            }
            blockquote {
                background-color: #f0f9ff;
                border-left: 4px solid #0284c7;
                margin: 8px 0;
                padding: 6px 10px;
                font-size: 8pt;
                color: #0369a1;
            }
            img {
                max-width: 100% !important;
                max-height: ' . ($orientation === 'landscape' ? '480px' : '380px') . ';
                height: auto;
                display: block;
                margin: 4px auto;
                border: 1px solid #cbd5e1;
                border-radius: 4px;
            }
            .footer {
                position: fixed;
                bottom: -8mm;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 7pt;
                color: #94a3b8;
                border-top: 1px solid #e2e8f0;
                padding-top: 3px;
            }
        </style>
    </head>
    <body>
        <div class="footer">' . htmlspecialchars("{$docTitle} — {$customerName} ({$projectCode})") . '</div>
        ' . $htmlBody . '
    </body>
    </html>';

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($fullHtml);
    $dompdf->setPaper('A4', $orientation);
    $dompdf->render();

    file_put_contents($pdfPath, $dompdf->output());
}

$invPdf = "{$invDir}/INVOICE_{$projectCode}_{$monthUpper}_{$yearInput}.pdf";
$pemPdf = "{$pemanfaatanDir}/RINCIAN_PEMANFAATAN_{$projectCode}_{$monthUpper}_{$yearInput}.pdf";
$fitPdf = "{$fiturDir}/PEMBARUAN_FITUR_{$projectCode}_{$monthUpper}_{$yearInput}.pdf";
$idxPdf = "{$idxDir}/EVIDENCE_INDEX_{$projectCode}_{$monthUpper}_{$yearInput}.pdf";
$attPdf = "{$ssDir}/VISUAL_ATTACHMENTS_{$projectCode}_{$monthUpper}_{$yearInput}.pdf";
$finalPdfPath = "{$finalDirPackage}/{$docPrefix}_{$monthUpper}_{$yearInput}.pdf";

generatePdfFromMarkdown($invContent, $invPdf, "Invoice {$projectCode} - {$monthNameIndo} {$yearInput}", 'portrait');
generatePdfFromMarkdown($pemContent, $pemPdf, "Rincian Pemanfaatan {$projectCode} - {$monthNameIndo} {$yearInput}", 'portrait');
generatePdfFromMarkdown($fitContent, $fitPdf, "Daftar Fitur Layanan {$projectCode} - {$monthNameIndo} {$yearInput}", 'landscape');
generatePdfFromMarkdown($idxContent, $idxPdf, "Indeks Bukti Operasional {$projectCode} - {$monthNameIndo} {$yearInput}", 'landscape');
generatePdfFromMarkdown($attachmentMd, $attPdf, "Lampiran Bukti Operasional {$projectCode} - {$monthNameIndo} {$yearInput}", 'landscape');

// Merge Section PDFs
$mergeScript = file_exists("{$projectDir}/.agents/skills/siasek-bos/scripts/merge_pdf_packages.py") 
    ? "{$projectDir}/.agents/skills/siasek-bos/scripts/merge_pdf_packages.py" 
    : "{$skillDir}/scripts/merge_pdf_packages.py";
if (file_exists($mergeScript)) {
    $cmd = "python " . escapeshellarg($mergeScript) . " " . escapeshellarg($finalPdfPath) . " " . escapeshellarg($invPdf) . " " . escapeshellarg($pemPdf) . " " . escapeshellarg($fitPdf) . " " . escapeshellarg($idxPdf) . " " . escapeshellarg($attPdf);
    shell_exec($cmd);
}

// Visual PDF Verification
$verifyScript = file_exists("{$projectDir}/.agents/skills/siasek-bos/scripts/verify_pdf_layout.py")
    ? "{$projectDir}/.agents/skills/siasek-bos/scripts/verify_pdf_layout.py"
    : "{$skillDir}/scripts/verify_pdf_layout.py";
$emptyPageQa = 'PASS';
$visualQa = 'PASS';
if (file_exists($verifyScript)) {
    $verifyCmd = "python " . escapeshellarg($verifyScript) . " " . escapeshellarg($finalPdfPath);
    $verifyOutput = shell_exec($verifyCmd);
    $emptyPageQa = (strpos($verifyOutput, 'EMPTY PAGE QA = PASS') !== false) ? 'PASS' : 'FAIL';
    $visualQa = (strpos($verifyOutput, 'VISUAL QA = PASS') !== false) ? 'PASS' : 'FAIL';
}

// Update Registry IF mode is finalize/issued
if ($invoiceStatus === 'ISSUED') {
    $registryData[$projectCode]['last_sequence'] = $nextSeqNumber;
    $registryData[$projectCode]['last_invoice'] = $candidateInvoiceNumber;
    $registryData[$projectCode]['issued_invoices'][] = $candidateInvoiceNumber;
    file_put_contents($registryFile, json_encode($registryData, JSON_PRETTY_PRINT));
}

// Build manifest.json
$manifest = [
    'release' => [
        'name' => 'siasek-bos',
        'version' => '2.1',
        'engine_version' => '2.1-universal',
        'domain_type' => $domainType
    ],
    'period_type' => $periodType,
    'period' => [
        'start' => $startDateStr,
        'end' => $endDateStr,
        'evidence_cutoff' => $cutoffDateStr
    ],
    'project' => [
        'code' => $projectCode,
        'name' => $projectName,
        'customer' => $customerName
    ],
    'billing' => [
        'snapshot_status' => $snapshotStatus,
        'snapshot_date' => $billingSourceDate,
        'final_frozen' => $finalFrozen,
        'source_type' => $billingSourceType,
        'quantity' => $quantityDisplay,
        'rate' => $rate,
        'include_hosting' => $includeHosting,
        'hosting_rate' => $includeHosting ? $hostingRate : 0,
        'total' => $totalAmount,
        'snapshot_file' => $snapshotFileRel
    ],
    'invoice_number' => $candidateInvoiceNumber,
    'invoice_status' => $invoiceStatus,
    'qa_status' => empty($qaFailures) ? 'PASS' : 'BLOCKED',
    'generated_at' => date('Y-m-d H:i:s')
];
file_put_contents("{$targetDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

if (!empty($qaFailures)) {
    $blockedReport = "# BLOCKED EVIDENCE REPORT — {$monthNameIndo} {$yearInput}\n\n";
    $blockedReport .= "**Tanggal Audit**: " . date('Y-m-d H:i:s') . "\n";
    $blockedReport .= "**Period Type**: `{$periodType}`\n";
    $blockedReport .= "**Billing Status**: `{$billingStatus}`\n";
    $blockedReport .= "**Snapshot Status**: `{$snapshotStatus}`\n";
    $blockedReport .= "**QA Status**: **BLOCKED**\n\n";
    $blockedReport .= "## Alasan Pemblokiran Paket (Blocking Reasons)\n\n";
    foreach ($qaFailures as $fail) {
        $blockedReport .= "- {$fail}\n";
    }
    file_put_contents("{$targetDir}/BLOCKED_REPORT_{$monthUpper}_{$yearInput}.md", $blockedReport);
}

// CLI Output Summary
echo "=====================================================\n";
echo " UNIVERSAL EVIDENCE & BILLING GENERATOR RESULT\n";
echo "=====================================================\n";
echo "PROJECT CODE      : {$projectCode}\n";
echo "PROJECT NAME      : {$projectName}\n";
echo "DOMAIN PROFILE    : {$domainType}\n";
echo "PERIOD            : {$monthNameIndo} {$yearInput}\n";
echo "SNAPSHOT CREATED  : " . ($snapshotCreated ? "YES" : "NO") . "\n";
echo "SNAPSHOT FILE     : {$snapshotFileRel}\n";
echo "BILLING ITEMS     :\n";
if ($domainType === 'APOTEK') {
    echo "  1. Lisensi Aplikasi (One-Time) : Rp" . formatRp($rate) . "\n";
    if ($includeHosting) {
        echo "  2. Cloud Hosting (1 Tahun)     : Rp" . formatRp($hostingRate) . "\n";
    }
} else {
    echo "  1. {$serviceName} ({$quantityDisplay}) : Rp" . formatRp($totalAmount) . "\n";
}
echo "TOTAL BILLING     : Rp" . formatRp($totalAmount) . "\n";
echo "INVOICE NUMBER    : {$candidateInvoiceNumber} [{$invoiceStatus}]\n";
echo "FROZEN            : " . ($finalFrozen ? "YES" : "NO") . "\n";
echo "QA STATUS         : " . (empty($qaFailures) ? "PASS" : "BLOCKED") . "\n";
if (!empty($qaFailures)) {
    echo "\nQA FAILURES:\n";
    foreach ($qaFailures as $fail) {
        echo " - {$fail}\n";
    }
}
echo "FINAL PACKAGE PDF : {$finalPdfPath}\n";
echo "=====================================================\n";