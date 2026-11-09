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
                'project/<projectId:\d+>/<controller:(question|informant|interpretation|report|concept)>' => '<controller>/index',
                'project/<projectId:\d+>/<controller:(question|informant|interpretation|report|concept)>/<action:[\w-]+>' => '<controller>/<action>',
                '<controller:[\w-]+>/<id:\d+>' => '<controller>/view',
                '<controller:[\w-]+>/<action:[\w-]+>/<id:\d+>' => '<controller>/<action>',
                '<controller:[\w-]+>/<action:[\w-]+>' => '<controller>/<action>',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
}

return $config;
