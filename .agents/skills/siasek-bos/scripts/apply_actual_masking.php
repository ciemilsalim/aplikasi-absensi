<?php
/**
 * SIASEK BOSP Image Privacy Masking Utility
 * Applies visual redaction bars/rectangles over PII (Student Names, NISN, Phone Numbers, Parent Info)
 * on evidence screenshots in docs/BOSP/live-evidence/masked/
 */

$origDir = __DIR__ . '/../../../../docs/BOSP/live-evidence/original';
$maskedDir = __DIR__ . '/../../../../docs/BOSP/live-evidence/masked';

if (!is_dir($maskedDir)) {
    mkdir($maskedDir, 0777, true);
}

function drawRedactionBox($im, $x1, $y1, $x2, $y2, $label = '[DATA DIRI DISENSOR]') {
    $width = imagesx($im);
    $height = imagesy($im);

    $x1 = max(0, min($width - 1, $x1));
    $x2 = max(0, min($width - 1, $x2));
    $y1 = max(0, min($height - 1, $y1));
    $y2 = max(0, min($height - 1, $y2));

    if ($x1 >= $x2 || $y1 >= $y2) return;

    $bgColor = imagecolorallocate($im, 15, 23, 42); // Dark slate #0f172a
    $borderColor = imagecolorallocate($im, 51, 65, 85); // Slate 700 #334155
    $textColor = imagecolorallocate($im, 148, 163, 184); // Slate 400 #94a3b8

    imagefilledrectangle($im, $x1, $y1, $x2, $y2, $bgColor);
    imagerectangle($im, $x1, $y1, $x2, $y2, $borderColor);

    $boxW = $x2 - $x1;
    $boxH = $y2 - $y1;
    if ($boxW >= 110 && $boxH >= 16) {
        $font = 2;
        $textY = (int)($y1 + ($boxH - 12) / 2);
        $textX = (int)($x1 + 8);
        imagestring($im, $font, $textX, $textY, $label, $textColor);
    }
}

// 1. EV-02: bukti_admin_leave_intervention_september_2026.png
$fileEv02Orig = "$origDir/bukti_admin_leave_intervention_september_2026.png";
$fileEv02Mask = "$maskedDir/bukti_admin_leave_intervention_september_2026.png";
if (file_exists($fileEv02Orig)) {
    $im = imagecreatefrompng($fileEv02Orig);
    for ($y = 510; $y < 2100; $y += 115) {
        drawRedactionBox($im, 325, $y, 640, $y + 42, '[SISWA REDACTED]');
    }
    imagepng($im, $fileEv02Mask);
    imagedestroy($im);
}

// 2. EV-03: bukti_08_teacher_dashboard.png
$fileEv03Orig = "$origDir/bukti_08_teacher_dashboard.png";
$fileEv03Mask = "$maskedDir/bukti_08_teacher_dashboard.png";
if (file_exists($fileEv03Orig)) {
    $im = imagecreatefrompng($fileEv03Orig);
    for ($y = 260; $y < 850; $y += 90) {
        drawRedactionBox($im, 1280, $y, 1720, $y + 35, '[NAMA SISWA DISENSOR]');
    }
    imagepng($im, $fileEv03Mask);
    imagedestroy($im);
}

// 3. EV-05: bukti_11_parent_dashboard.png
$fileEv05Orig = "$origDir/bukti_11_parent_dashboard.png";
$fileEv05Mask = "$maskedDir/bukti_11_parent_dashboard.png";
if (file_exists($fileEv05Orig)) {
    $im = imagecreatefrompng($fileEv05Orig);
    drawRedactionBox($im, 340, 155, 820, 225, '[NAMA SISWA & NISN DISENSOR]');
    for ($y = 480; $y < 850; $y += 100) {
        drawRedactionBox($im, 340, $y, 750, $y + 35, '[DATA ANAK DISENSOR]');
    }
    imagepng($im, $fileEv05Mask);
    imagedestroy($im);
}

echo "Image privacy masking completed.\n";
