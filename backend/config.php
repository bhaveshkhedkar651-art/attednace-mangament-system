<?php

require_once __DIR__ . '/db.php';

$appUrl = $_ENV['APP_URL'] ?? null;
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');

return [
    'app_url'         => $appUrl,
    'qr_valid_seconds'=> max(1, (int)($_ENV['QR_VALID_SECONDS'] ?? 36000)),
    'upload_dir'      => __DIR__ . '/../uploads/students/',
    'upload_url'      => $basePath . '/uploads/students/',
    'max_upload_size' => 2 * 1024 * 1024, 
    'allowed_mimes'   => ['image/jpeg', 'image/png', 'image/webp'],
    'timezone'        => 'Asia/Kolkata',
];