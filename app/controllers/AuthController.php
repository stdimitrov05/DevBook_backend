<?php

namespace App\Controllers;

use App\Exceptions\HttpExceptions\Http404Exception;
use App\Exceptions\HttpExceptions\Http422Exception;
use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use App\Services\AbstractService;

/**
 * @\App\Controllers\AuthController
 * @AuthController
 * @uses  \App\Controllers\AbstractController
 */
class AuthController extends AbstractController
{
    /**
     * Index
     * @return null
     */
    public function jwtAction()
    {
        try {
            $response = $this->authService->index();

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_ALREADY_EXISTS,
                AbstractService::ERROR_REDIS_NOT_SET_DATA
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };

        }

        return $response;
    }

}
