<?php

// คัดลอกเป็น config/params-local.php แล้วใส่ค่าจริง (ไฟล์นี้ไม่ถูก commit)
return [
    'cookieValidationKey' => '', // สุ่มด้วย: php -r 'echo bin2hex(random_bytes(24));'
    // https://console.cloud.google.com/apis/credentials  -> OAuth client ID (Web application)
    // Authorized redirect URI: https://<your-host>/site/auth?authclient=google
    'google.clientId' => '',
    'google.clientSecret' => '',

    // https://developers.line.biz/console/  -> LINE Login channel
    // Callback URL: https://<your-host>/site/auth?authclient=line
    // ถ้าต้องการอีเมล ให้ขอสิทธิ์ "OpenID Connect > Email address permission" ในหน้า channel
    'line.clientId' => '',
    'line.clientSecret' => '',

    'legacyDb' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=nurseapp_legacy',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
