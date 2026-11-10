<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'qualitative-research',
    'name' => $params['appName'],
    'basePath' => dirname(__DIR__),
    'language' => 'th-TH',
    'sourceLanguage' => 'en-US',
    'timeZone' => 'Asia/Bangkok',
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => $params['cookieValidationKey'] ?? '',
            // เว็บอยู่ที่ root ของโดเมนเสมอ (ทั้งกรณี Document root = web/ และกรณีใช้ .htaccess ที่ root ส่งเข้า web/)
            // กำหนดไว้เพื่อให้ลิงก์ไม่มี /web และ Redirect URI ของ Google/LINE ตรงกับที่ตั้งไว้
            'baseUrl' => '',
            'scriptUrl' => '/index.php',
            // เชื่อ X-Forwarded-Proto จาก nginx บนเครื่องเดียวกัน (ให้ระบบรู้ว่าเป็น https)
            'trustedHosts' => ['127.0.0.1', '::1'],
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
        ],
        'authClientCollection' => [
            'class' => \yii\authclient\Collection::class,
            'clients' => array_filter([
                'google' => $params['google.clientId'] ? [
                    'class' => \yii\authclient\clients\Google::class,
                    'clientId' => $params['google.clientId'],
                    'clientSecret' => $params['google.clientSecret'],
                ] : null,
                'line' => $params['line.clientId'] ? [
                    'class' => \app\components\authclient\Line::class,
                    'clientId' => $params['line.clientId'],
                    'clientSecret' => $params['line.clientSecret'],
                ] : null,
            ]),
        ],
        'formatter' => [
            'locale' => 'th-TH',
            'defaultTimeZone' => 'Asia/Bangkok',
            'nullDisplay' => '<span class="text-muted">-</span>',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
                'project/<id:\d+>' => 'project/view',
                'project/<projectId:\d+>/<controller:(question|informant|interpretation|report|concept|book)>' => '<controller>/index',
                'project/<projectId:\d+>/<controller:(question|informant|interpretation|report|concept|book)>/<action:[\w-]+>' => '<controller>/<action>',
                '<controller:[\w-]+>/<id:\d+>' => '<controller>/view',
                '<controller:[\w-]+>/<action:[\w-]+>/<id:\d+>' => '<controller>/<action>',
                '<controller:[\w-]+>/<action:[\w-]+>' => '<controller>/<action>',
            ],
        ],
    ],
    'params' => $params,
];

// เครื่องมือพัฒนา: เปิดเฉพาะโหมด dev และเฉพาะเมื่อติดตั้งแพ็กเกจไว้ (vendor ของเซิร์ฟเวอร์จริงไม่มี)
if (YII_ENV_DEV && class_exists(\yii\debug\Module::class)) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];
}
if (YII_ENV_DEV && class_exists(\yii\gii\Module::class)) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
}

// ทะลุหน้า login ในโหมด dev (php yii serve เท่านั้น — production ไม่มีทางเข้าบล็อกนี้)
// เข้าเป็นผู้ใช้ id ตาม params 'dev.autoLoginUserId' ถ้าไม่ระบุใช้ admin ที่ active คนแรก
// ตั้ง 'dev.autoLoginUserId' => false ใน params-local.php เพื่อปิด
// หน้าแรก (/) และหน้าแนะนำระบบไม่ login ให้ — เห็นหน้าแนะนำระบบเหมือนเซิร์ฟเวอร์จริง กด "เข้าสู่ระบบ" แล้วจะ login ให้ทันที
if (YII_ENV_DEV && ($params['dev.autoLoginUserId'] ?? null) !== false) {
    $config['on beforeAction'] = static function (\yii\base\ActionEvent $event) use ($params): void {
        $app = Yii::$app;
        if (!$app->user->isGuest
            || in_array($event->action->uniqueId, ['site/index', 'site/auth', 'site/logout', 'site/error', 'site/welcome'], true)
            || preg_match('#^(debug|gii)/#', $event->action->uniqueId)) {
            return;
        }
        $id = $params['dev.autoLoginUserId'] ?? null;
        $user = $id
            ? \app\models\User::findIdentity($id)
            : \app\models\User::find()
                ->where(['role' => \app\models\User::ROLE_ADMIN, 'status' => \app\models\User::STATUS_ACTIVE])
                ->orderBy('id')->one();
        if ($user) {
            $app->user->login($user);
        }
    };
}

return $config;
