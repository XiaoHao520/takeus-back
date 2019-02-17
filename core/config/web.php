<?php

$params = require __DIR__ . '/params.php';

$config = [
    'id' => 'basic',
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'sentry'],
    'components' => [
        'request' => [
            'cookieValidationKey' => '123',
        ],
        'cache' => [
            'class' => env('CACHE_CLASS', 'yii\caching\FileCache'),
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'admin' => [
            'class' => 'yii\web\User',
            'identityClass' => 'app\models\Admin',
            'enableAutoLogin' => true,
            'idParam' => '__admin_id',
            'identityCookie' => [
                'name' => '_admin_identity',
                'httpOnly' => true,
            ],
        ],
        'errorHandler' => [
            'errorView' => __DIR__ . '/../views/error/error.php',
            'exceptionView' => __DIR__ . '/../views/error/exception.php',
        ],
        'mailer' => [
            // 'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            // 'useFileTransport' => true,
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@app/mail',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.qq.com',
                'port' => '465',
                'encryption' => 'ssl',
            ],
            'messageConfig' => [
                'charset' => 'UTF-8',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => YII_ENV_DEV ? [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logVars' => [],
                    'logFile' => '@runtime/logs/app.log',
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['trace'],
                    'logVars' => [],
                    'logFile' => '@runtime/logs/full.log',
                ],
            ] : [],
        ],
        'db' => require __DIR__ . '/db.php',
        // 'urlManager' => [
        //     'enablePrettyUrl' => true,
        //     'showScriptName' => false,
        //     'rules' => [
        //     ],
        // ],
        'session' => [
            'class' => 'yii\web\DbSession',
            'name' => 'DBSESSIONID',
        ],
        'serializer' => [
            'class' => 'app\hejiang\Serializer',
        ],
        'sentry' => [
            'class' => 'app\hejiang\Sentry',
            'options' => [
                'dsn' => 'https://72fae1b1870b45f6ac603f1a5b34f556:74a490e3ba1a464d802855ac47cac826@sentry.io/1212625',
                'timeout' => 5,
                'tags' => [
                    'hj_version' => hj_core_version(),
                ],
            ],
        ],

        ],
        'modules' => [
            'api' => [
                'class' => 'app\modules\api\Module',
            ],
            'mch' => [
                'class' => 'app\modules\mch\Module',
            ],
            'admin' => [
                'class' => 'app\modules\admin\Module',
            ],
            'user' => [
                'class' => 'app\modules\user\Module',
            ],
        ],
        'params' => $params,
    ];

switch ($config['components']['cache']['class']) {
    case 'yii\caching\MemCache':
        $config['components']['cache']['servers'] = [
            [
                'host' => env('MEMCACHE_SERVER_HOST', '127.0.0.1'),
                'port' => env('MEMCACHE_SERVER_PORT', 11211),
                'weight' => env('MEMCACHE_SERVER_WEIGHT', 100),
            ],
        ];
        break;
    default:
        break;
}

if (YII_ENV_DEV || YII_DEBUG) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*', '::1','223.74.129.190'],
    ];

    $config['bootstrap'][] = 'log';
} else {
    $config['bootstrap'][] = 'sentry';
}
return $config;