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

    // AI ผ่าน OpenRouter (ใส่ key จริงใน config/params-local.php — ว่าง = ปิดฟีเจอร์ AI ทั้งหมด)
    'openrouter.apiKey' => '',
    // ใช้รุ่นถูกที่สุดเป็นค่าเริ่มต้น — ถ้าคุณภาพไม่พอเปลี่ยนเป็นรุ่นอื่นใน ai.prices ด้านล่าง
    'ai.model' => 'google/gemini-2.5-flash-lite',
    'ai.reasoningEffort' => null, // null = ไม่ให้โมเดล "คิด" ก่อนตอบ (ถูกสุด) / 'low' = คิดเล็กน้อย
    'ai.batchSize' => 10, // จำนวนคำตอบต่อการกด 1 ครั้ง (มากไปจะเกินเวลา request)
    // ราคา USD ต่อ 1 ล้าน token [input, output] — ตรวจราคาล่าสุดที่ https://openrouter.ai/models
    'ai.prices' => [
        'google/gemini-2.5-flash-lite' => [0.10, 0.40],
        'google/gemini-3.1-flash-lite' => [0.25, 1.50],
        'anthropic/claude-opus-5.5' => [4.00, 20.00],
        'anthropic/claude-sonnet-5.5' => [2.00, 10.00],
        'anthropic/claude-haiku-5.5' => [0.10, 0.50],
    ],
    // เครดิต = ceil(USD × usdToThb × markup ÷ bahtPerCredit)
    'ai.usdToThb' => 34.0,     // อัตราแลกเปลี่ยน (ปรับตามจริง)
    'ai.markup' => 30,         // ขายที่ 30 เท่าของต้นทุน (รวมค่าธรรมเนียม OpenRouter, ค่าเงิน, ค่าเซิร์ฟเวอร์ และกำไร)
                               // ระวัง: ถ้าเปลี่ยน ai.model เป็น Claude Sonnet/Opus ราคาต่อครั้งจะสูงขึ้นหลายร้อยเท่า
    'ai.bahtPerCredit' => 0.10, // 1 เครดิต = 10 สตางค์ (10 เครดิต = 1 บาท)
    'ai.welcomeCredits' => 5,   // แจกครั้งเดียวเมื่อผู้ใช้ใหม่ได้เปิดใช้งาน (0 = ไม่แจก)

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
