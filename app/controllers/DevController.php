<?php

namespace App\Controllers;

use App\Exceptions\HttpExceptions\Http422Exception;
use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use App\Services\AbstractService;

/**
 * @UsersController
 * @\App\Controllers\UsersController
 */
class DevController extends AbstractController
{
    /**
     * createUsersIndex
     */

    public function createUsersAction()
    {
        $this->elastic->createUsersIndex();
    }

    /**
     * createAvatarsIndex
     */

    public function createAvatarsAction()
    {
        $this->elastic->createAvatarIndex();
    }
}
