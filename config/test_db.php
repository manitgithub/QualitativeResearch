<?php

$db = require __DIR__ . '/db.php';
// ฐานข้อมูลทดสอบแยก — ห้ามชี้ไปที่ฐานข้อมูลจริง (ทุก test จะล้างข้อมูลในตาราง)
// ไม่ใช้ user/รหัสผ่านจาก db.php (อาจเป็นค่าของเซิร์ฟเวอร์จริง) — แก้ได้ใน config/test_db-local.php
$db['dsn'] = 'mysql:host=127.0.0.1;dbname=qualitative_test';
$db['username'] = 'root';
$db['password'] = '';
$db['enableSchemaCache'] = false;

if (is_file(__DIR__ . '/test_db-local.php')) {
    $db = array_merge($db, require __DIR__ . '/test_db-local.php');
}

return $db;
