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
    const ERROR_USER_NOT_AUTHORIZED = 11020;

    /**
     * User errors
     */
    const ERROR_UNABLE_CREATE_USER = 11001;
    const ERROR_USER_NOT_FOUND = 11002;
     const ERROR_UNABLE_UPDATE_USER = 11004;
    const ERROR_PASSWORD_NOT_FOUND = 11006;
    const ERROR_PASSWORD_INCORRECT = 11007;
    const ERROR_PASSWORD_NOT_MATCH = 11008;

    /*
     * Email confirmation errors
     */
    const ERROR_CONFIRMATION_TOKEN_NOT_EXIST = 11010;
    const ERROR_CONFIRMATION_TOKEN_EXPIRED = 11011;
    const ERROR_CONFIRMATION_CONFIRMED = 11012;

    /*
     * Auth errors
     */
    const ERROR_WRONG_EMAIL_OR_PASSWORD = 13001;
    const ERROR_USER_NOT_ACTIVE = 13002;
    const ERROR_USER_BANNED = 13003;
    const ERROR_USER_SUSPENDED = 13004;
    const ERROR_USER_HAS_DELETED = 13010;
    const ERROR_MISSING_TOKEN = 13005;
    const ERROR_EXPIRED_TOKEN = 13006;
    const ERROR_BAD_TOKEN = 13008;
    const ERROR_EMAIL_NOT_EXIST = 13009;
    const ERROR_RESET_TOKEN_NOT_EXIST = 13020;
    const ERROR_RESET_TOKEN_EXPIRED = 13021;

}
