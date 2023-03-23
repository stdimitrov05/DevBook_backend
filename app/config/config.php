<?php
return new \Phalcon\Config\Config(
    [
        'db' => [
            'adapter' => getenv('DB_ADAPTER'),
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'dbname' => getenv('DB_NAME'),
            'charset' => getenv('DB_CHARSET'),
            'collation' => getenv('DB_COLLATION')
        ],
        'application' => [
            'logInDb' => true,
            'migrationsDir' => APP_PATH . '/migrations',
            'migrationsTsBased' => true,
            'controllersDir' => APP_PATH . "/controllers/",
            'modelsDir' => APP_PATH . "/models/",
            'emailsDir' => APP_PATH . '/views/emails/',
            'logsDir' => BASE_PATH . '/tmp/logs/',
            'baseUri' => "/",
            'domain' => getenv('DOMAIN'),
            'publicUrl' => "http://" . getenv("DOMAIN"),
            'mediaUrl' => "http://devbook.test/images/tools/",
        ],
        'redis' => [
            'redisHost' => getenv('REDIS_HOST'),
            'redisPort' => getenv('REDIS_PORT'),
            'usersPrefix' => getenv('REDIS_USERS_PREFIX'),
            'jtiPostfix' => getenv('REDIS_JTI_POSTFIX'),
            'csrfPrefix' => getenv('REDIS_CSRF_PREFIX'),
            'whiteListPrefix' => getenv('REDIS_WHITE_LIST_PREFIX')
        ],
        'mail' => [
            'noreplyEmail' => getenv('NOREPLY_EMAIL'),
            'noreplyName' => getenv('NOREPLY_NAME'),
            'emailHost' => getenv('EMAIL_HOST'),
            'emailPort' => getenv('EMAIL_PORT'),
            'emailSmtpSecure' => getenv('SMTPSECURE'),
        ],
        'auth' => [
            'key' => getenv('JWT_KEY'),
            'accessTokenExpire' => getenv('JWT_ACCESS_TOKEN_EXPIRE'),
            'refreshTokenExpire' => getenv('JWT_REFRESH_TOKEN_EXPIRE'),
            'refreshTokenRememberExpire' => getenv('JWT_REFRESH_TOKEN_REMEMBER_EXPIRE'),
            'ignoreUri' => [
                '/',
                '/locations',
                '/signup',
            ]
        ],
    ]
);
