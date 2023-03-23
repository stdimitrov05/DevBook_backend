<?php

namespace App\Services;


use App\Lib\Elastic;
use App\Lib\JWT;

/**
 * Class AbstractService
 *
 * @property \Phalcon\Db\Adapter\Pdo\Mysql $db
 * @property \Phalcon\Config\Config $config
 * @property AuthService $authService
 * @property UsersService $usersService
 * @property Elastic $elastic
 * @property \Redis $redis
 * @property RedisService $redisService
 * @property JWT $jwt
 */
abstract class AbstractService extends \Phalcon\DI\Injectable
{
    /**
     * Invalid parameters anywhere
     */
    const ERROR_INVALID_PARAMETERS = 10000;
    // Record  not found
    const  ERROR_IS_NOT_FOUND = 11000;
    const  ERROR_UNABLE_TO_CREATE = 11010;
    const  ERROR_UNABLE_TO_DELETE = 11020;
    const ERROR_UNABLE_TO_UPDATE = 11030;
    const ERROR_UNABLE_TO_STORE = 11040;

    // Users errors
    const ERROR_USER_NOT_ACTIVE = 12000;
    const ERROR_WRONG_EMAIL_OR_PASSWORD = 12010;
    const ERROR_ACCOUNT_DELETED = 12020;
    const ERROR_USER_NOT_AUTHORIZED = 12030;

    // JWT errors
    const ERROR_BAD_TOKEN = 13000;

    // Email errors
    const ERROR_TOKEN_HAS_CONFIRMED = 15000;

}
