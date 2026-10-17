<?php

require_once __DIR__ . '/DiscountService.php';

$fee = 100.0;
$expected = 10.0;
$actual = DiscountService::calculateEducationalDiscount($fee);

if ($actual !== $expected) {
    $file = str_replace('\\', '/', __DIR__ . '/DiscountService.php');
    fwrite(STDERR, "AssertionFailedError: Expected response [{$expected}] but received [{$actual}] at {$file}:12\n");
    exit(1);
}

echo "OK (1 passed)\n";
exit(0);
