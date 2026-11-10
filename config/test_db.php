<?php

$db = require __DIR__ . '/db.php';
// ฐานข้อมูลทดสอบแยก — ห้ามชี้ไปที่ฐานข้อมูลจริง (ทุก test จะล้างข้อมูลในตาราง)
$db['dsn'] = 'mysql:host=127.0.0.1;dbname=qualitative_test';
$db['enableSchemaCache'] = false;

return $db;
