<?php

namespace App\Controllers;

use App\Exceptions\HttpExceptions\Http422Exception;
use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use App\Services\AbstractService;
use App\Validation\ChangePasswordAsLoggedValidation;

/**
 * Frontend controller
 */
class UsersController extends AbstractController
{
    /**
     * changePasswordAction
     *
     * @return array
     */
    public function changePasswordAction()
    {
        $data = [];

        // Collect and trim request params
        foreach ($this->request->getPost() as $key => $value) {
            $data[$key] = $this->request->getPost($key, ['string', 'trim']);
        }

        // Start validation
        $validation = new ChangePasswordAsLoggedValidation();
        $messages = $validation->validate($data);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $this->usersService->changePassword($data);
        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_PASSWORD_NOT_FOUND:
                case AbstractService::ERROR_PASSWORD_INCORRECT:
                case AbstractService::ERROR_PASSWORD_NOT_MATCH:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        return null;
    }

}
