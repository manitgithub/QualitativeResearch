<?php

declare(strict_types=1);

// โหมดพัฒนาเฉพาะตอนรันด้วย PHP built-in server (`php yii serve`) บนเครื่องพัฒนาเท่านั้น
// เซิร์ฟเวอร์จริง (Apache / nginx / PHP-FPM) เป็น production เสมอ — ไม่ต้องแก้ไฟล์นี้ก่อนอัปโหลด
$isDevServer = PHP_SAPI === 'cli-server';
// เซิร์ฟเวอร์จริง: เปิดรายละเอียด error ชั่วคราวเฉพาะคนที่มีรหัสลับ — ดูวิธีใน config/debug.php
$debugByKey = !$isDevServer && (require __DIR__ . '/../config/debug.php')();
defined('YII_ENV') or define('YII_ENV', $isDevServer ? 'dev' : 'prod');
defined('YII_DEBUG') or define('YII_DEBUG', $isDevServer || $debugByKey);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
