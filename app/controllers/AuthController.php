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
     * signupAction
     * @retrun  null
     */

    public function signupAction()
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
            $response = $this->usersService->create((array)$data);

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
     * loginAction
     * @return array
     */
    public function loginAction(): array
    {
        $data = [];

        // Collect and trim request params
        foreach ($this->request->getPost() as $key => $value) {
            $data[$key] = $this->request->getPost($key, ['string', 'trim']);
        }

        // Start validation
        $validation = new LoginValidation();
        $messages = $validation->validate($data);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $response = $this->authService->login((array)$data);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_UNABLE_TO_CREATE,
                AbstractService::ERROR_USER_NOT_ACTIVE,
                AbstractService::ERROR_ACCOUNT_DELETED,
                AbstractService::ERROR_WRONG_EMAIL_OR_PASSWORD,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };

        }

        return $response;
    }

    /**
     * refreshJWTAction
     * @retrun  array
     */

    public function refreshJWTAction(): array
    {
        try {
            $tokens = $this->authService->refreshJwtTokens();
        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_BAD_TOKEN,
                AbstractService::ERROR_UNABLE_TO_DELETE,
                AbstractService::ERROR_UNABLE_TO_CREATE
                => new Http404Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };
        }

        return $tokens;
    }

    /**
     * forgotPasswordAction
     * @retrun  null
     */

    public function forgotPasswordAction()
    {
        // Get email
        $email = $this->request->getPost();

        // Start validation
        $validation = new ForgotPasswordValidation();
        $messages = $validation->validate($email);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $response = $this->authService->forgotPassword((string)$email['email']);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_USER_NOT_ACTIVE,
                AbstractService::ERROR_ACCOUNT_DELETED,
                AbstractService::ERROR_UNABLE_TO_CREATE,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };

        }

        return $response;

    }

    /**
     * emailConfirmAction
     * @retrun  null
     */
    public function emailConfirmAction()
    {
        $confirmToken = $this->request->getPost();

        // Start validation
        $validation = new EmailConfirmValidation();
        $messages = $validation->validate($confirmToken);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $response = $this->authService->emailConfirm((string)$confirmToken['token']);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_TOKEN_HAS_CONFIRMED,
                AbstractService::ERROR_NOT_FOUND,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };

        }

        return $response;
    }


    /**
     * resendEmailConfirmAction
     * @retrun null
     */


    public function resendEmailConfirmAction()
    {
        $confirmToken = $this->request->getPost();

        // Start validation
        $validation = new EmailConfirmValidation();
        $messages = $validation->validate($confirmToken);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $response = $this->authService->resendEmailConfirm((string)$confirmToken['token']);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_TOKEN_HAS_CONFIRMED,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                AbstractService::ERROR_NOT_FOUND,
                => new Http404Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };

        }

        return $response;
    }

    /**
     * checkRestPasswordTokenAction
     * @retrun array
     */
    public function checkRestPasswordTokenAction(): array
    {
        $resetToken = $this->request->getPost();

        // Start validation
        $validation = new EmailConfirmValidation();
        $messages = $validation->validate($resetToken);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $response = $this->authService->checkResetToken((string)$resetToken['token']);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_BAD_TOKEN,
                AbstractService::ERROR_TOKEN_HAS_CONFIRMED,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };

        }

        return $response;
    }

    /**
     * changeForgotPasswordAction
     * @retrun array
     */

    public function changeForgotPasswordAction()
    {
        $data = [];

        // Collect and trim request params
        foreach ($this->request->getPost() as $key => $value) {
            $data[$key] = $this->request->getPost($key, ['string', 'trim']);
        }

        // Start validation
        $validation = new ChangeFrogotPasswordValidation();
        $messages = $validation->validate($data);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $response = $this->authService->changeForgotPassword($data);

        } catch (ServiceException $e) {
            throw match ($e->getCode()) {
                AbstractService::ERROR_NOT_FOUND,
                => new Http404Exception($e->getMessage(), $e->getCode(), $e),
                AbstractService::ERROR_WRONG_EMAIL_OR_PASSWORD,
                AbstractService::ERROR_TOKEN_HAS_CONFIRMED,
                AbstractService::ERROR_UNABLE_TO_UPDATE,
                => new Http422Exception($e->getMessage(), $e->getCode(), $e),
                default => new Http500Exception('Internal Server Error', $e->getCode(), $e),
            };

        }

        return $response;
    }
}
