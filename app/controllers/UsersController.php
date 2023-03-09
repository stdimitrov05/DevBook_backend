<?php

namespace App\Controllers;

use App\Exceptions\HttpExceptions\Http400Exception;
use App\Exceptions\HttpExceptions\Http404Exception;
use App\Exceptions\HttpExceptions\Http422Exception;
use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use App\Services\AbstractService;
use App\Validation\BillingValidation;
use App\Validation\EditUserDetailsValidation;
use App\Validation\SignupValidation;

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
                AbstractService::ERROR_BAD_TOKEN,
                => new Http404Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };
        }

        return $response;
    }

    /**
     * billingAction
     * @param int $userId
     * @retrun null
     * */
    public function billingAction(int $userId)
    {
        $data = [];

        // Collect and trim request params
        foreach ($this->request->getPost() as $key => $value) {
            $data[$key] = $this->request->getPost($key, ['string', 'trim']);
        }

        // Start validation
        $validation = new BillingValidation();
        $messages = $validation->validate($data);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }
        try {
            $response = $this->usersService->billing($userId, $data);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_USER_NOT_AUTHORIZED,
                AbstractService::ERROR_UNABLE_TO_CREATE,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                AbstractService::ERROR_BAD_TOKEN,
                => new Http404Exception($e->getMessage(), $e->getCode(), $e),

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
                AbstractService::ERROR_BAD_TOKEN,
                => new Http404Exception($e->getMessage(), $e->getCode(), $e),

                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };
        }

        return $response;
    }

    /**
     * updateDetailsAction
     * @param int $userId
     * @retrun  null
     * */
    public function updateDetailsAction(int $userId)
    {
        $data = [];

        // Collect and trim request params
        foreach ($this->request->getPost() as $key => $value) {
            $data[$key] = $this->request->getPost($key, ['string', 'trim']);
        }

        // Start validation
        $validation = new EditUserDetailsValidation();
        $messages = $validation->validate($data);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $response = $this->usersService->updateDetails($userId, $data);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_USER_NOT_AUTHORIZED,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                AbstractService::ERROR_BAD_TOKEN,
                AbstractService::ERROR_IS_NOT_FOUND,
                => new Http404Exception($e->getMessage(), $e->getCode(), $e),

                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };
        }

        return $response;
    }

}
