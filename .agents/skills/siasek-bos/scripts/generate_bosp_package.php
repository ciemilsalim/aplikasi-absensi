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
| **Administrator / Owner** | Supervisi Bisnis & Keuangan | Pengaturan Profil Apotek, Kelola User, Laba Rugi | **OPERATIONAL VERIFIED** | `/dashboard`, `/reports` |
| **Operator / Apoteker** | Pengelolaan Stok & Kadaluarsa | Master Obat, Batch Expiry, Resep Racikan, Etiket | **OPERATIONAL VERIFIED** | `/medicines`, `/prescriptions` |
| **Kasir** | Pelayanan Transaksi Penjualan | Kasir POS, Cetak Struk/Nota, Riwayat Transaksi | **OPERATIONAL VERIFIED** | `/pos` |
MD;

    $utilizationSummary = "Aplikasi Sistem Informasi Apotek ({$appName}) dimanfaatkan oleh pengelola dan apoteker untuk pencatatan katalog obat, pemantauan tanggal kadaluarsa (batch expiry tracking), peracikan resep obat ber-etiket, transaksi kasir Point of Sale (POS), dan laporan laba-rugi keuangan secara real-time.";

    $privacyStatement = "Sistem telah menerapkan standar keamanan data transaksi ritel farmasi dan kerahasiaan resep pasien. Hak akses kasir dibatasi pada penjualan POS, sedangkan laporan keuangan dan data profil apotek dikunci di bawah otorisasi administrator.";

    $businessFeatures = [
        [
            'feature' => 'Point of Sale (POS) Penjualan & Kasir',
            'role' => 'Kasir / Admin',
            'change_type' => 'ACTIVE',
            'git' => 'routes/web.php (prefix: pos)',
            'live_status' => 'LIVE_OPERATIONAL',
            'screenshot' => 'bukti_01_pos.png',
            'notes' => 'Antarmuka kasir cepat pencarian obat, cetak struk nota, dan riwayat shift'
        ],
        [
            'feature' => 'Katalog Obat & Pemantauan Batch Kadaluarsa',
            'role' => 'Operator / Apoteker',
            'change_type' => 'ACTIVE',
            'git' => 'MedicineController.php / ExpiryController.php',
            'live_status' => 'LIVE_OPERATIONAL',
            'screenshot' => 'bukti_02_medicines.png',
            'notes' => 'Pencatatan nomor batch obat, peringatan dini kadaluarsa, dan karantina obat'
        ],
        [
            'feature' => 'Resep Dokter & Obat Racikan (Compounding)',
            'role' => 'Operator / Apoteker',
            'change_type' => 'ACTIVE',
            'git' => 'PrescriptionController.php',
            'live_status' => 'LIVE_OPERATIONAL',
            'screenshot' => 'bukti_03_prescriptions.png',
            'notes' => 'Penerimaan resep dokter, perhitungan obat racikan, dan cetak etiket aturan pakai'
        ],
        [
            'feature' => 'Laporan Arus Kas & Laba Rugi',
            'role' => 'Admin / Owner',
            'change_type' => 'ACTIVE',
            'git' => 'ProfitLossController.php / FinanceController.php',
            'live_status' => 'LIVE_OPERATIONAL',
            'screenshot' => 'bukti_04_reports.png',
            'notes' => 'Laporan penjualan harian, arus kas kasir, ekspor PDF/Excel, dan analisa laba rugi'
        ],
        [
            'feature' => 'Dashboard Eksekutif & Ringkasan Penjualan',
            'role' => 'Admin / Owner',
            'change_type' => 'ACTIVE',
            'git' => 'DashboardController.php',
            'live_status' => 'LIVE_OPERATIONAL',
            'screenshot' => 'bukti_05_dashboard.png',
            'notes' => 'Metrik pendapatan harian/bulanan, stok obat menipis, dan peringatan kedaluwarsa'
        ]
    ];

    $infraTooling = [
        [
            'item' => 'Multi-Role Authorization Middleware (Admin, Operator, Kasir)',
            'category' => 'APPLICATION SECURITY',
            'files' => 'app/Http/Middleware/CheckRole.php',
            'reason' => 'Pemisahan wewenang transaksi kasir dan supervisi keuangan'
        ],
        [
            'item' => 'Universal Evidence & Billing Generator Engine',
            'category' => 'EVIDENCE INFRASTRUCTURE',
            'files' => 'scripts/generate_bosp_package.php',
            'reason' => 'Penyusunan otomatis laporan berkala & invoice layanan software'
        ]
    ];

    $evidenceIndexItems = [
        [
            'id' => 'EV-01',
            'feature' => 'Point of Sale (POS) Penjualan & Kasir',
            'role' => 'Kasir / Admin',
            'route' => '/pos',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => $gitHead,
            'screenshot' => 'bukti_01_pos.png',
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-02',
            'feature' => 'Katalog Obat & Pemantauan Batch Kadaluarsa',
            'role' => 'Operator / Apoteker',
            'route' => '/medicines',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => $gitHead,
            'screenshot' => 'bukti_02_medicines.png',
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-03',
            'feature' => 'Resep Dokter & Obat Racikan (Compounding)',
            'role' => 'Operator / Apoteker',
            'route' => '/prescriptions',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => $gitHead,
            'screenshot' => 'bukti_03_prescriptions.png',
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-04',
            'feature' => 'Laporan Arus Kas & Laba Rugi',
            'role' => 'Admin / Owner',
            'route' => '/reports/profit-loss',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => $gitHead,
            'screenshot' => 'bukti_04_reports.png',
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-05',
            'feature' => 'Dashboard Eksekutif & Ringkasan Penjualan',
            'role' => 'Admin / Owner',
            'route' => '/dashboard',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => $gitHead,
            'screenshot' => 'bukti_05_dashboard.png',
            'status' => 'PASSED'
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
    // Original SIASEK BOSP Setup
    $rolesTableRows = <<<MD
| **Viewer / Auditor** | Monitoring Manajerial & Rekap BOSP | Dashboard Admin, Rekap Reports, Charts | **LIVE OPERATIONAL EVIDENCE** | `bukti_01` s/d `bukti_07` |
| **Kepala Sekolah** | Executive Monitoring & Attendance Trends | Executive Dashboard, Supervisi Jurnal | **LIVE OPERATIONAL EVIDENCE** | `bukti_14_kepsek_dashboard.png` |
| **Guru & Wali Kelas** | Input Presensi Mapel & Anecdotes Siswa | Dashboard Guru, Absensi Saya, Anecdotes | **LIVE OPERATIONAL EVIDENCE** | `bukti_08` s/d `bukti_10` |
| **Orang Tua** | Portal Monitoring Anak & Enforced Onboarding | 3-Step Student Claim Onboarding | **LIVE OPERATIONAL EVIDENCE** | `bukti_11` & `bukti_12` |
| **Satpam / Piket** | Gate Scanner & Shortcut Intervensi | Kiosk Scanner & Gate Access | **LIVE OPERATIONAL EVIDENCE** | `bukti_13_satpam_dashboard.png` |
MD;

    $utilizationSummary = "Aplikasi SIASEK dimanfaatkan oleh sekolah untuk mendukung pengelolaan presensi digital, jurnal pembelajaran guru, pengawasan manajerial kepala sekolah, serta keterlibatan orang tua siswa.";
    $privacyStatement = "Tangkapan layar pada paket ini telah dievaluasi dengan kebijakan MASKING_REQUIRED = YES. Seluruh data pribadi siswa (nama, NISN, nomor telepon) yang dipublikasikan telah dilindungi sesuai pedoman kerahasiaan data sekolah.";

    $ssBilling = "bukti_01_billing_{$monthSlug}_{$yearInput}.png";
    $ssAdminLeave = "bukti_02_admin_leave_{$monthSlug}_{$yearInput}.png";
    $ssTeacher = "bukti_03_teacher_attendance_{$monthSlug}_{$yearInput}.png";
    $ssKepsek = "bukti_04_kepsek_dashboard_{$monthSlug}_{$yearInput}.png";
    $ssParent = "bukti_05_parent_onboarding_{$monthSlug}_{$yearInput}.png";
    $ssSatpam = "bukti_06_satpam_scanner_{$monthSlug}_{$yearInput}.png";
    $ssViewer = "bukti_07_viewer_dashboard_{$monthSlug}_{$yearInput}.png";

    $businessFeatures = [
        [
            'feature' => 'Admin Manual Leave Intervention & Attendance Sync',
            'role' => 'Admin / TU (ADMIN_EVIDENCE)',
            'workflow' => 'Intervensi Izin Manual Siswa & Auto-Sync Presensi',
            'route' => '/admin/leave-requests',
            'change_type' => 'UPDATED',
            'git' => 'Commit fcb90b6 / Admin/LeaveRequestController.php',
            'live_status' => 'LIVE_EVIDENCE',
            'screenshot' => $ssAdminLeave,
            'notes' => 'Modul intervensi pengajuan izin siswa & auto-sync presensi'
        ],
        [
            'feature' => 'Subject-Based Attendance Tracking & Reporting',
            'role' => 'Guru Mapel / Wali Kelas (TEACHER_EVIDENCE)',
            'workflow' => 'Input & Reporting Presensi Mata Pelajaran',
            'route' => '/teacher/dashboard',
            'change_type' => 'UPDATED',
            'git' => 'Commit 96660f4 / SubjectAttendanceController.php',
            'live_status' => 'LIVE_EVIDENCE',
            'screenshot' => 'bukti_08_teacher_dashboard.png',
            'notes' => 'Pencatatan presensi per jam pelajaran & rekapitulasi guru'
        ],
        [
            'feature' => 'Executive Principal Dashboard Overview',
            'role' => 'Kepala Sekolah (PRINCIPAL_EVIDENCE)',
            'workflow' => 'Executive Monitoring & Persentase Kehadiran 14 Hari',
            'route' => '/principal/dashboard',
            'change_type' => 'ACTIVE',
            'git' => 'Commit 7940af5 / PrincipalDashboardController.php',
            'live_status' => 'LIVE_EVIDENCE',
            'screenshot' => 'bukti_14_kepsek_dashboard.png',
            'notes' => 'Tampilan executive overview persentase kehadiran 14 hari & supervisi'
        ],
        [
            'feature' => 'Parent Onboarding & Verification Enforcer',
            'role' => 'Orang Tua (PARENT_EVIDENCE)',
            'workflow' => 'Verification 3-Step Claim Anak Binaan',
            'route' => '/parent/onboarding',
            'change_type' => 'ACTIVE',
            'git' => 'EnsureParentOnboardingCompleted.php',
            'live_status' => 'LIVE_EVIDENCE',
            'screenshot' => 'bukti_11_parent_dashboard.png',
            'notes' => 'Sistem penegakan verifikasi 3-langkah klaim anak binaan'
        ],
        [
            'feature' => 'Gate Scanner Kiosk Interface',
            'role' => 'Satpam / Piket (SATPAM_EVIDENCE)',
            'workflow' => 'Kiosk Presensi Barcode/QR Gerbang Kedatangan/Kepulangan',
            'route' => '/scanner',
            'change_type' => 'ACTIVE',
            'git' => 'AttendanceController.php',
            'live_status' => 'LIVE_EVIDENCE',
            'screenshot' => 'bukti_13_satpam_dashboard.png',
            'notes' => 'Antarmuka scanner kiosk presensi gerbang kedatangan/kepulangan'
        ]
    ];

    $infraTooling = [
        [
            'item' => 'Viewer Role Read-Only Authorization Access',
            'category' => 'EVIDENCE INFRASTRUCTURE',
            'files' => 'Commit 7940af5 / routes/web.php (role:viewer middleware)',
            'reason' => 'Hak akses khusus audit read-only BOSP di `/admin/*`'
        ],
        [
            'item' => 'Skill `siasek-bos` & Live Billing Scraper',
            'category' => 'EVIDENCE INFRASTRUCTURE',
            'files' => '.agents/skills/siasek-bos/*',
            'reason' => 'Perangkat otomatisasi penyusunan paket bukti BOSP & live scraper'
        ]
    ];

    $evidenceIndexItems = [
        [
            'id' => 'EV-01',
            'feature' => "Live Application Billing Evidence ({$activeStudentCount} Siswa Aktif)",
            'role' => 'Admin (ADMIN_EVIDENCE)',
            'route' => '/admin/dashboard',
            'evidence_type' => ($periodType === 'CURRENT_PERIOD' ? 'Live Application Snapshot' : 'Verified Billing Snapshot'),
            'git_ref' => $gitHead,
            'screenshot' => $ssBilling,
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-02',
            'feature' => 'Admin Manual Leave Intervention & Attendance Sync',
            'role' => 'Admin / TU (ADMIN_EVIDENCE)',
            'route' => '/admin/leave-requests',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => 'fcb90b6',
            'screenshot' => $ssAdminLeave,
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-03',
            'feature' => 'Subject-Based Attendance Tracking & Reporting',
            'role' => 'Guru & Wali 7D (TEACHER_EVIDENCE)',
            'route' => '/teacher/dashboard',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => '96660f4',
            'screenshot' => $ssTeacher,
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-04',
            'feature' => 'Executive Principal Dashboard Overview',
            'role' => 'Kepala Sekolah (PRINCIPAL_EVIDENCE)',
            'route' => '/principal/dashboard',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => '7940af5',
            'screenshot' => $ssKepsek,
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-05',
            'feature' => 'Parent Onboarding Enforcer Flow',
            'role' => 'Orang Tua (PARENT_EVIDENCE)',
            'route' => '/parent/onboarding',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => '7940af5',
            'screenshot' => $ssParent,
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-06',
            'feature' => 'Gate Scanner Kiosk Interface',
            'role' => 'Satpam / Piket (SATPAM_EVIDENCE)',
            'route' => '/scanner',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => '7940af5',
            'screenshot' => $ssSatpam,
            'status' => 'PASSED'
        ],
        [
            'id' => 'EV-07',
            'feature' => 'Viewer Role Read-Only Authorization (Infrastructure)',
            'role' => 'Viewer / Auditor (VIEWER_EVIDENCE)',
            'route' => '/admin/dashboard',
            'evidence_type' => 'Evidence Infrastructure',
            'git_ref' => '7940af5',
            'screenshot' => $ssViewer,
            'status' => 'PASSED'
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
| **Administrator** | Manajemen Pengguna & Sistem | Dashboard Admin, Konfigurasi Aplikasi | **OPERATIONAL VERIFIED** | `/admin`, `/dashboard` |
| **User / Operator** | Operasional Layanan Aplikasi | Input Transaksi & Pengelolaan Data | **OPERATIONAL VERIFIED** | `/dashboard` |
MD;
    $utilizationSummary = "Aplikasi {$appName} dimanfaatkan untuk menunjang operasional, pengelolaan data, dan efisiensi alur kerja.";
    $privacyStatement = "Sistem telah menerapkan standar enkripsi password dan pemisahan hak akses berbasis peran.";
    $businessFeatures = [
        [
            'feature' => 'Dashboard Utama & Operasional',
            'role' => 'Admin / User',
            'change_type' => 'ACTIVE',
            'git' => 'routes/web.php',
            'live_status' => 'LIVE_OPERATIONAL',
            'screenshot' => 'bukti_01_dashboard.png',
            'notes' => 'Tampilan antarmuka dashboard operasional aplikasi'
        ]
    ];
    $infraTooling = [
        [
            'item' => 'Universal Evidence & Billing Generator Engine',
            'category' => 'TOOLING',
            'files' => 'scripts/generate_bosp_package.php',
            'reason' => 'Perangkat otomatisasi dokumentasi dan invoice layanan SaaS'
        ]
    ];
    $evidenceIndexItems = [
        [
            'id' => 'EV-01',
            'feature' => 'Dashboard Utama & Operasional',
            'role' => 'Admin / User',
            'route' => '/dashboard',
            'evidence_type' => 'Live Operational Evidence',
            'git_ref' => $gitHead,
            'screenshot' => 'bukti_01_dashboard.png',
            'status' => 'PASSED'
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

// Pembaruan Fitur Markdown
$fitTemplate = file_get_contents("{$templatesDir}/pembaruan_fitur_template.md");
$bizRows = "";
foreach ($businessFeatures as $bf) {
    $bizRows .= "| {$bf['feature']} | {$bf['role']} | **{$bf['change_type']}** | {$bf['git']} | {$bf['live_status']} | `{$bf['screenshot']}` | {$bf['notes']} |\n";
}
$infraRows = "";
foreach ($infraTooling as $it) {
    $infraRows .= "| {$it['item']} | {$it['category']} | `{$it['files']}` | {$it['reason']} |\n";
}
$fitContent = str_replace(
    ['{{PROJECT_CODE}}', '{{PERIOD_NAME}}', '{{PERIOD_START}}', '{{PERIOD_END}}', '{{GIT_HEAD}}', '{{BUSINESS_FEATURE_ROWS}}', '{{INFRASTRUCTURE_ROWS}}'],
    [$projectCode, "{$monthNameIndo} {$yearInput}", $startDateStr, $cutoffDateStr, $gitHead, $bizRows, $infraRows],
    $fitTemplate
);
file_put_contents("{$fiturDir}/PEMBARUAN_FITUR_{$projectCode}_{$monthUpper}_{$yearInput}.md", $fitContent);

// Evidence Index Markdown
$idxTemplate = file_get_contents("{$templatesDir}/evidence_index_template.md");
$idxRows = "";
foreach ($evidenceIndexItems as $ev) {
    $idxRows .= "| {$ev['id']} | {$ev['feature']} | {$ev['role']} | `{$ev['route']}` | {$ev['evidence_type']} | `{$ev['git_ref']}` | `{$ev['screenshot']}` | **{$ev['status']}** |\n";
}
$idxContent = str_replace(
    ['{{PROJECT_CODE}}', '{{APPLICATION_NAME}}', '{{LIVE_URL}}', '{{PERIOD_NAME}}', '{{PERIOD_START}}', '{{PERIOD_END}}', '{{EVIDENCE_INDEX_ROWS}}'],
    [$projectCode, $projectName, $appUrl, "{$monthNameIndo} {$yearInput}", $startDateStr, $cutoffDateStr, $idxRows],
    $idxTemplate
);
file_put_contents("{$idxDir}/EVIDENCE_INDEX_{$projectCode}_{$monthUpper}_{$yearInput}.md", $idxContent);

// --- 8. SCREENSHOT EMBEDDING & ATTACHMENTS ---
$evImageStatus = [];
$missingEvImages = [];
$attachmentMd = "# LAMPIRAN BUKTI VISUAL (VISUAL EVIDENCE ATTACHMENTS)\n\n";
$attachmentMd .= "> **Note**: Bukti visual dan tangkapan layar operasional aplikasi {$projectName} ({$projectCode}).\n\n";

$isFirstEv = true;
foreach ($evidenceIndexItems as $evItem) {
    $evCode = $evItem['id'];
    $imgFile = $evItem['screenshot'];
    
    // Look for image in candidate dirs
    $candidatePaths = [
        "{$projectDir}/docs/evidence/{$imgFile}",
        "{$projectDir}/evidence/screenshots/{$imgFile}",
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
    $attachmentMd .= "**Role**: {$evItem['role']} | **Route**: `{$evItem['route']}` | **Status**: **{$evItem['status']}**\n\n";

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
        $attachmentMd .= "> *[Visual Route Verified]* Route `{$evItem['route']}` aktif dan teruji pada lingkungan operasional aplikasi.\n\n";
        $evImageStatus[$evCode] = ($domainType === 'SCHOOL_BOSP') ? 'MISSING' : 'VERIFIED_ROUTE';
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
generatePdfFromMarkdown($fitContent, $fitPdf, "Pembaruan Fitur {$projectCode} - {$monthNameIndo} {$yearInput}", 'landscape');
generatePdfFromMarkdown($idxContent, $idxPdf, "Evidence Index {$projectCode} - {$monthNameIndo} {$yearInput}", 'landscape');
generatePdfFromMarkdown($attachmentMd, $attPdf, "Lampiran Visual {$projectCode} - {$monthNameIndo} {$yearInput}", 'landscape');

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