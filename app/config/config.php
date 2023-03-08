<?php
return new \Phalcon\Config\Config(
    [
        'database' => [
            'adapter' => getenv('DATABASE_ADAPTER'),
            'host' => getenv('DATABASE_HOST'),
            'username' => getenv('DATABASE_USERNAME'),
            'password' => getenv('DATABASE_PASSWORD'),
            'dbname' => getenv('DATABASE_NAME'),
            'charset' => getenv('DATABASE_CHARSET'),
            'collation' => getenv('DATABASE_COLLATION')
        ],
        'application' => [
            'logInDb' => true,
            'migrationsDir' => APP_PATH.'/migrations',
            'migrationsTsBased' => true,
            'controllersDir' =>APP_PATH. "/controllers/",
            'modelsDir' => APP_PATH. "/models/",
            'emailsDir' => APP_PATH . '/views/emails/',
            'logsDir' => BASE_PATH . '/tmp/logs/',
            'baseUri' => "/",
            'domain' => getenv('DOMAIN'),
            'publicUrl' => "http://" . getenv("DOMAIN"),
            'mediaUrl' => "http://devbook.test/images/tools/",
        ],
        'mail' => [
            'noreplyEmail' => getenv('NOREPLY_EMAIL'),
            'noreplyName' => getenv('NOREPLY_NAME')
        ],
        'auth' => [
            'key' => getenv('JWT_KEY'),
            'accessTokenExpire' => getenv('JWT_ACCESS_TOKEN_EXPIRE'),
            'refreshTokenExpire' => getenv('JWT_REFRESH_TOKEN_EXPIRE'),
            'refreshTokenRememberExpire' => getenv('JWT_REFRESH_TOKEN_REMEMBER_EXPIRE'),
            'ignoreUri' => [
                '/',
                '/countries',
                '/signup',
                '/login',
                '/forgotPassword',
                '/check/forgotPassword/token',
                '/users/email/confirm',
                '/users/resend/email/confirm',
                '/forgot-password/change-password',
            ]
        ],
    ]
);
