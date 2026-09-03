<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$status = $app->handleCommand(new \Symfony\Component\Console\Input\ArrayInput(['command' => 'migrate', '--force' => true]));
echo "Migration exit code: " . $status . "\n";
echo "submission_source: " . (\Illuminate\Support\Facades\Schema::hasColumn('leave_requests', 'submission_source') ? 'EXISTS' : 'MISSING') . "\n";
echo "created_by: " . (\Illuminate\Support\Facades\Schema::hasColumn('leave_requests', 'created_by') ? 'EXISTS' : 'MISSING') . "\n";
