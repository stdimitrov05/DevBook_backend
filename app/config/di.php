<?php

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\DI\DI;
use Phalcon\Logger\Logger;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Session\Adapter\Redis;

// Initializing a DI Container
$di = new DI();

/** Register dispatcher service */
$di->setShared('dispatcher', new Phalcon\Mvc\Dispatcher());

/** Register router service */
$di->setShared('router', new Phalcon\Mvc\Router());

/** Register request service */
$di->setShared('request', new Phalcon\Http\Request());

/** Register modelsManager service */
$di->setShared('modelsManager', new Phalcon\Mvc\Model\Manager());

/** Register modelsMetadata service */
$di->setShared('modelsMetadata', new Phalcon\Mvc\Model\MetaData\Memory());

/** Register eventsManager service */
$di->setShared('eventsManager', new Phalcon\Events\Manager());

/** Register config service */
$di->setShared('config', $config);

/** Register filter service */
$di->setShared('filter', function () {
    $factory = new \Phalcon\Filter\FilterFactory();
    return $factory->newInstance();
});


/** Register security service */
$di->setShared('security', new Phalcon\Encryption\Security());

/**********************************************
 * Custom services
 **********************************************/

// Redis service
$di->setShared('redis', function () use ($config) {
    $redis = new \Redis();
    try {
        $redis->connect(
            $config->redis->redisHost,
            $config->redis->redisPort
        );
        return $redis;
    } catch (RedisException $exception) {
        throw  new Exception("Redis: " . $exception->getTraceAsString());
    }
});


/** Mail service */
$di->setShared('mailer', function () use ($config) {
    $phpMailer = new PHPMailer\PHPMailer\PHPMailer();
    $phpMailer->isSMTP();
    $phpMailer->SMTPSecure = $config->mail->emailSmtpSecure;
    $phpMailer->Host = $config->mail->emailHost;
    $phpMailer->SMTPAuth = true;
    $phpMailer->Port = $config->mail->emailPort;
    $phpMailer->Username = $config->mail->noreplyEmail;
    $phpMailer->Password = getenv("NOREPLY_PASSWORD");

    return new \App\Lib\Mailer($phpMailer);
});

/** Elasticsearch service */
$di->setShared('elastic', function () use ($config) {
    $elastic = \Elastic\Elasticsearch\ClientBuilder::create()
        ->setHosts((array)'elasticsearch:9200')->build();

    return new \App\Lib\Elastic($elastic);
});

$di->set(
    'logger',
    function () {
        $adapter = new Stream(BASE_PATH . '/tmp/logs/main.log');
        return new Logger('messages', ['main' => $adapter]);
    }
);

/**
 * Overriding Response-object to set the Content-type header globally
 */
$di->setShared(
    'response',
    function () {
        $response = new \Phalcon\Http\Response();
        $response->setContentType('application/json', 'utf-8');

        return $response;
    }
);

/** Database */
$di->setShared(
    "db",
    function () use ($config, $di) {

        $eventsManager = new \Phalcon\Events\Manager();

        $connection = new Mysql(
            [
                "host" => $config->db->host,
                "username" => $config->db->username,
                "password" => $config->db->password,
                "dbname" => $config->db->dbname,
                "charset" => $config->db->charset,
                "collation" => $config->db->collation,
                'options' => [
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false
                ]
            ]
        );

        // Assign the eventsManager to the db adapter instance
        $connection->setEventsManager($eventsManager);

        return $connection;
    }
);

$di->setShared('frontendService', '\App\Services\FrontendService');
$di->setShared('authService', '\App\Services\AuthService');
$di->setShared('usersService', '\App\Services\UsersService');
$di->setShared('redisService', '\App\Services\RedisService');
$di->setShared('jwt', '\App\Lib\JWT');

return $di;
