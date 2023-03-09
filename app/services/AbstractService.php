<?php

namespace App\Services;


use App\Lib\Elastic;

/**
 * Class AbstractService
 *
 * @property \Phalcon\Db\Adapter\Pdo\Mysql $db
 * @property \Phalcon\Config\Config $config
 * @property AuthService $authService
 * @property UsersService $usersService
 * @property Elastic $elastic
 * @property \Redis $redis
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

    // Record is not found
    const  ERROR_IS_NOT_FOUND = 10040;

    // Record is can`t created
    const  ERROR_UNABLE_TO_CREATE = 10050;
    const  ERROR_BAD_TOKEN = 10060;
    const  ERROR_UNABLE_TO_UPDATE= 10070;
    const  ERROR_UNABLE_TO_INSERT= 10080;
    const ERROR_USER_NOT_AUTHORIZED = 11020;

    // Redis errors
    const ERROR_REDIS_NOT_SET_DATA = 11030;

//    Users errors
    const ERROR_USER_NOT_ACTIVE = 12010;
    const ERROR_WRONG_EMAIL_OR_PASSWORD = 12020;
    const ERROR_WRONG_PASSWORD = 12030;
    const ERROR_TOKEN_HAS_CONFIRMED = 12040;
    const ERROR_ACCOUNT_IS_DELETED = 12050;
    const ERROR_UNABLE_TO_DELETE_ACCOUNT = 12060;

    // JWT errors
    const ERROR_JWT_IN_WHITE_LIST = 13000;
    const ERROR_JWT_IS_NOT_FOUND = 13010;
    const ERROR_HAS_EXPIRED = 13020;
    const ERROR_JWT_CANT_REMOVE = 13030;

    // Avatar
    const ERROR_FORMAT_IS_NOT_SUPPORT = 14000;

}
