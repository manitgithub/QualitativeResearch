<?php

/**
 * ค่าเชื่อมต่อฐานข้อมูลหลัก
 * ค่าจริง (รหัสผ่าน) ให้ใส่ใน config/db-local.php ซึ่งไม่ถูก commit
 */
$db = [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host=127.0.0.1;dbname=qualitative',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'enableSchemaCache' => !YII_DEBUG,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];

if (is_file(__DIR__ . '/db-local.php')) {
    $db = array_merge($db, require __DIR__ . '/db-local.php');
}

return $db;
