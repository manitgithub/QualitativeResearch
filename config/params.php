<?php

$params = [
    'appName' => 'ระบบวิเคราะห์ข้อมูลเชิงคุณภาพ',
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Qualitative Research',
    'user.rememberMeDuration' => 3600 * 24 * 30,

    // ผู้ใช้ใหม่ที่เข้าระบบด้วย Google/LINE ครั้งแรก ให้ใช้งานได้ทันทีหรือต้องรอ admin อนุมัติ
    'socialSignupRequiresApproval' => true,

    // OAuth (ใส่ค่าจริงใน config/params-local.php)
    'google.clientId' => '',
    'google.clientSecret' => '',
    'line.clientId' => '',
    'line.clientSecret' => '',

    // ฐานข้อมูลระบบเดิม (Yii1) สำหรับคำสั่ง ./yii legacy/import
    'legacyDb' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=nurseapp_legacy',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];

if (is_file(__DIR__ . '/params-local.php')) {
    $params = array_merge($params, require __DIR__ . '/params-local.php');
}

return $params;
