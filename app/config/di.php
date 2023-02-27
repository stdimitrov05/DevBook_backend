<?php

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\DI\DI;
use Phalcon\Logger\Logger;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Session\Adapter\Redis;
use Phalcon\Session\Manager;
use Phalcon\Storage\AdapterFactory;
use Phalcon\Storage\SerializerFactory;


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
    $redis->connect(getenv('REDIS_HOST'),getenv('REDIS_PORT'));
    return $redis;
});


/** Mail service */
$di->setShared('mailer', function () use ($config) {
    $phpMailer = new PHPMailer\PHPMailer\PHPMailer();
    $phpMailer->isSMTP();
    $phpMailer->SMTPSecure = "tls";
    $phpMailer->Host = 'mail.phalcon.ml';
    $phpMailer->SMTPAuth = true;
    $phpMailer->Port = 587;
    $phpMailer->Username = getenv("NOREPLY_EMAIL");
    $phpMailer->Password = getenv("NOREPLY_PASSWORD");

    return new \App\Lib\Mailer($phpMailer);
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
                "host" => $config->database->host,
                "username" => $config->database->username,
                "password" => $config->database->password,
                "dbname" => $config->database->dbname,
                "charset" => $config->database->charset,
                "collation" => $config->database->collation,
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

return $di;
