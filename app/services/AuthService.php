<?php

namespace App\Services;

use App\Exceptions\HttpExceptions\Http403Exception;
use App\Exceptions\ServiceException;
use App\Lib\Helper;
use App\Models\LoginsFailed;
use App\Models\Users;
use Phalcon\Db\Column;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;

/**
 * Business-logic for site frontend
 *
 * @AuthService
 * @\App\Services\AuthService
 * @uses \App\Services\AbstractService
 */
class AuthService extends AbstractService
{


}
