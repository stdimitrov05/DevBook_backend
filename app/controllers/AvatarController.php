<?php

namespace App\Controllers;

use App\Exceptions\HttpExceptions\Http422Exception;
use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use App\Services\AbstractService;

/**
 * Frontend controller
 */
class AvatarController extends AbstractController
{
    /**
     * Allow a user to sign up to the system
     */
    public function uploadAction()
    {
        $data = $_FILES;

        try {
            //Passing data to business logic and prepare the response
            $token = $this->avatarsService->upload($data);

        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_FORMAT_IS_NOT_SUPPORT:
                case AbstractService::ERROR_UNABLE_CREATE_AVATAR:
                case AbstractService::ERROR_USER_NOT_FOUND:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        return $token;
    }


}
