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
            'controllersDir' => "app/controllers/",
            'modelsDir' => "app/models/",
            'emailsDir' => APP_PATH . '/views/emails/',
            'logsDir' => BASE_PATH . '/tmp/logs/',
            'baseUri' => "/",
            'domain' => getenv('DOMAIN'),
            'publicUrl' => "https://" . getenv("DOMAIN"),
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
                '/signup:POST',
                '/login:POST',
            ]
        ],
    ]
);
