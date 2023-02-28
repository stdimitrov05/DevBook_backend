<?php

namespace App\Services;


/**
 * Class AbstractService
 *
 * @property \Phalcon\Db\Adapter\Pdo\Mysql $db
 * @property \Phalcon\Config\Config $config
 */
abstract class AbstractService extends \Phalcon\DI\Injectable
{
    /**
     * Invalid parameters anywhere
     */
    const ERROR_INVALID_PARAMETERS = 10010;

    /**
     * Record already exists
     */
    const ERROR_ALREADY_EXISTS = 10020;
    const ERROR_NOT_EXISTS = 10030;
    const ERROR_USER_NOT_AUTHORIZED = 11020;

    // Redis errors
    const ERROR_REDIS_NOT_SET_DATA = 11030;

//    Users errors
    const ERROR_USER_NOT_ACTIVE = 12010;
    const ERROR_WRONG_EMAIL_OR_PASSWORD = 12020;


}
