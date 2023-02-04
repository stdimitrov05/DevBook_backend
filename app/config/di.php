<?php

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\DI;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\Stream;


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
$di->setShared('security', new Phalcon\Security());

/**********************************************
 * Custom services
 **********************************************/

/** Mail service */
$di->setShared('mailer', function () use ($config) {
    return new \App\Lib\Mailer();
});

$di->set(
    'logger',
    function () {
        $adapter = new Stream( APP_PATH . '/logs/main.log');
        $logger  = new Logger(
            'messages',
            [
                'main' => $adapter,
            ]
        );

        return $logger;
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
                "host"     => $config->database->host,
                "username" => $config->database->username,
                "password" => $config->database->password,
                "dbname"   => $config->database->dbname,
                "charset"  => $config->database->charset,
                "collation"=> $config->database->collation,
                'options' => [
                    PDO::ATTR_DEFAULT_FETCH_MODE  =>  PDO::FETCH_ASSOC,
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
$di->setShared('usersService', '\App\Services\UsersService');
$di->setShared('authService', '\App\Services\AuthService');

return $di;
