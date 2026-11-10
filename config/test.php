<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/test_db.php';

/**
 * Application configuration shared by all test types
 */
return [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        \app\tests\Support\MailerBootstrap::class,
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'name' => $params['appName'],
    'language' => 'th-TH',
    'timeZone' => 'Asia/Bangkok',
    'components' => [
        'db' => $db,
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'messageClass' => \yii\symfonymailer\Message::class,
            'useFileTransport' => true,
            'viewPath' => '@app/mail',
        ],
        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
        ],
        'urlManager' => [
            'showScriptName' => true,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'loginUrl' => ['site/login'],
        ],
        'authClientCollection' => [
            'class' => \yii\authclient\Collection::class,
            'clients' => [
                'google' => ['class' => \yii\authclient\clients\Google::class, 'clientId' => 'test-google', 'clientSecret' => 'x'],
                'line' => ['class' => \app\components\authclient\Line::class, 'clientId' => '1234567890', 'clientSecret' => 'x'],
            ],
        ],
        'formatter' => ['locale' => 'th-TH', 'defaultTimeZone' => 'Asia/Bangkok'],
        'cache' => ['class' => \yii\caching\ArrayCache::class],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
            // but if you absolutely need it set cookie domain to localhost
            /*
            'csrfCookie' => [
                'domain' => 'localhost',
            ],
            */
        ],
    ],
    'params' => $params,
];
