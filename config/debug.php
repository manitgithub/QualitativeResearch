<?php

/**
 * สวิตช์ debug ชั่วคราวบนเซิร์ฟเวอร์จริง — เห็นรายละเอียด error เฉพาะคนที่มีรหัสลับ
 *
 * เปิด:  สร้างไฟล์ config/debug-local.php (ไม่ถูก commit) ที่มีเนื้อหา
 *            <?php return 'รหัสลับยาวอย่างน้อย 24 ตัวอักษร';
 *        แล้วเปิดเว็บด้วย  https://<โดเมน>/?qr_debug=<รหัสลับ>  ครั้งเดียว
 *        เบราว์เซอร์นั้นจะเห็นรายละเอียด error ไป 1 ชั่วโมง (ผ่าน cookie) — คนอื่นยังเห็นหน้า error ปกติ
 * เปิดทั้งเว็บ (ทุกคนเห็นรายละเอียด error — ใช้ชั่วคราวเท่านั้น):
 *        config/debug-local.php มีเนื้อหา   <?php return true;
 * ปิด:   ลบไฟล์ config/debug-local.php  (ห้ามเปิดทิ้งไว้)
 *
 * @return callable(): bool
 */
return static function (): bool {
    $file = __DIR__ . '/debug-local.php';
    if (!is_file($file)) {
        return false;
    }
    $value = require $file;
    if ($value === true) {
        return true; // เปิดทั้งเว็บ
    }
    $key = is_string($value) ? $value : '';
    if (strlen($key) < 24) {
        return false; // รหัสสั้นเกินไป — ไม่เปิดให้
    }
    $fromQuery = isset($_GET['qr_debug']) && is_string($_GET['qr_debug']) && hash_equals($key, $_GET['qr_debug']);
    $fromCookie = isset($_COOKIE['qr_debug']) && is_string($_COOKIE['qr_debug']) && hash_equals($key, $_COOKIE['qr_debug']);
    if ($fromQuery && !headers_sent()) {
        setcookie('qr_debug', $key, [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    return $fromQuery || $fromCookie;
};
