<?php

namespace App\Controllers;

use App\Exceptions\HttpExceptions\Http404Exception;
use App\Exceptions\HttpExceptions\Http422Exception;
use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use App\Services\AbstractService;
use App\Validation\ChangeFrogotPasswordValidation;
use App\Validation\EmailConfirmValidation;
use App\Validation\ForgotPasswordValidation;
use App\Validation\LoginValidation;
use App\Validation\SignupValidation;

/**
 * @\App\Controllers\AuthController
 * @AuthController
 * @uses  \App\Controllers\AbstractController
 */
class AuthController extends AbstractController
{

    /**
     * Creates a new user account using the provided request data.
     * @return array An array containing the newly created user's information.
     * @throws Http422Exception If the provided request data fails validation.
     * @throws Http500Exception If an error occurs while creating the user account.
     */
    public function signup(): array
    {
        $data = [];
        // Collect and trim request params
        foreach ($this->request->getPost() as $key => $value) {
            $data[$key] = $this->request->getPost($key, ['string', 'trim']);
        }
        // Start validation
        $validation = new SignupValidation();
        $messages = $validation->validate($data);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $response = $this->usersService->create($data);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_UNABLE_TO_CREATE,
                AbstractService::ERROR_BAD_TOKEN,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };

        }

        return $response;
    }

    /**
     * Retrieves the sign-up data required by the front-end form, including the tokens for the captcha and the list of available locations.
     *
     * @return array An array containing the captcha tokens and locations.
     * @throws Http422Exception If the service is unable to create or store data.
     * @throws Http500Exception If an internal server error occurs.
     */
    public function getSignUpData(): array
    {
        try {
            $response['tokens'] = $this->authService->getCaptcha();
            $response['locations'] = $this->frontendService->getLocations();
        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_UNABLE_TO_CREATE,
                AbstractService::ERROR_UNABLE_TO_STORE,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };
        }

        return $response;
    }

}
