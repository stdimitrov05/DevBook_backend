<?php

namespace App\Controllers;

use App\Exceptions\HttpExceptions\Http400Exception;
use App\Exceptions\HttpExceptions\Http422Exception;
use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use App\Services\AbstractService;

/**
 * @UsersController
 * @\App\Controllers\UsersController
 */
class UsersController extends AbstractController
{
    /**
     * userDetailsAction
     * @param int $userId
     * @retrun array
     */
    public function userDetailsAction(int $userId) : array
    {
        try {
            $response = $this->usersService->details($userId);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_USER_NOT_AUTHORIZED,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                AbstractService::ERROR_IS_NOT_FOUND,
                => new Http400Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };
        }

        return $response;
    }

    /**
     * deleteAction
     * @param int $userId
     * @retrun  null
     * */
    public function deleteAction(int $userId)
    {
        try {
            $response = $this->usersService->delete($userId);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_USER_NOT_AUTHORIZED,
                AbstractService::ERROR_ACCOUNT_IS_DELETED,
                AbstractService::ERROR_UNABLE_TO_DELETE_ACCOUNT,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                AbstractService::ERROR_IS_NOT_FOUND,
                => new Http400Exception($e->getMessage(), $e->getCode(), $e),

                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };
        }

        return $response;
    }

}
