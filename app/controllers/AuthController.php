<?php

namespace App\Controllers;

use App\Exceptions\HttpExceptions\Http400Exception;
use App\Exceptions\HttpExceptions\Http404Exception;
use App\Exceptions\HttpExceptions\Http422Exception;
use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use App\Services\AbstractService;
use App\Services\AuthService;
use App\Validation\ChangePasswordValidation;
use App\Validation\ImageValidation;
use App\Validation\LoginValidation;
use App\Validation\ForgotPasswordValidation;
use App\Validation\SignupValidation;

class AuthController extends AbstractController
{
    /**
     * Allow a user to sign up to the system
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
            //Passing data to business logic and prepare the response
            $token = $this->usersService->createUser($data);

        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_UNABLE_CREATE_USER:
                case AbstractService::ERROR_UNABLE_CREATE_AVATAR:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        return $token;
    }

    /**
     * Allow a user to sign up to the system
     */
    public function uploadAvatartAction()
    {
        $data = $_FILES;

        try {
            //Passing data to business logic and prepare the response
            $token = $this->usersService->uploadedImage($data);

        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_UNABLE_CREATE_USER:
                case AbstractService::ERROR_FORMAT_IS_NOT_SUPPORT:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        return $token;
    }

    /**
     * Logging user
     */
    public function loginAction()
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
            $jwt = $this->authService->checkLogin($data);

        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_WRONG_EMAIL_OR_PASSWORD:
                case AbstractService::ERROR_USER_NOT_ACTIVE:
                case AbstractService::ERROR_USER_BANNED:
                case AbstractService::ERROR_USER_SUSPENDED:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        // Return access and refresh tokens
        return $jwt;
    }

    /**
     * Refresh the user tokens with refresh token
     */
    public function refreshJwtTokensAction()
    {
        try {
            $tokens = $this->authService->refreshJwtTokens();
        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_USER_NOT_FOUND:
                    throw new Http404Exception($e->getMessage(), $e->getCode(), $e);
                case AbstractService::ERROR_MISSING_TOKEN:
                case AbstractService::ERROR_BAD_TOKEN:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        return $tokens;
    }

    /**
     * Forgot password form
     */
    public function forgotPasswordAction()
    {
        $data = [];

        // Collect and trim request params
        foreach ($this->request->getPost() as $key => $value) {
            $data[$key] = $this->request->getPost($key, ['string', 'trim']);
        }

        // Start validation
        $validation = new ForgotPasswordValidation();
        $messages = $validation->validate($data);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $this->authService->forgotPassword($data['email']);
        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_EMAIL_NOT_EXIST:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        // Return null on success
        return null;
    }

    /**
     * Verify reset password token action
     */
    public function verifyResetPasswordTokenAction()
    {
        $errors = [];
        $token = $this->request->getPost('token');

        if (empty($token)) {
            $errors['token'] = 'Missing token';
        }

        if ($errors) {
            $exception = new Http400Exception(
                'Input parameters validation error',
                self::ERROR_INVALID_REQUEST
            );
            throw $exception->addErrorDetails($errors);
        }

        try {
            $token = $this->authService->verifyResetPasswordToken($token);

        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_RESET_TOKEN_NOT_EXIST:
                case AbstractService::ERROR_RESET_TOKEN_EXPIRED:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        // Return token on success
        return $token;
    }

    /**
     * Change password from reset password
     */
    public function changePasswordAction()
    {
        $data = [];

        // Collect and trim request params
        foreach ($this->request->getPost() as $key => $value) {
            $data[$key] = $this->request->getPost($key, ['string', 'trim']);
        }

        // Start validation
        $validation = new ChangePasswordValidation();
        $messages = $validation->validate($data);

        if (count($messages)) {
            $this->throwValidationErrors($messages);
        }

        try {
            $this->authService->changePassword($data);
        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_RESET_TOKEN_NOT_EXIST:
                case AbstractService::ERROR_RESET_TOKEN_EXPIRED:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        return null;
    }

    /**
     * Confirm user email
     *
     * @return null
     */
    public function confirmEmailAction()
    {
        $errors = [];
        $token = trim($this->request->getPost('token'));

        if (empty($token)) {
            $errors['token'] = 'Missing token';
        }

        if ($errors) {
            $exception = new Http400Exception(
                'Input parameters validation error',
                self::ERROR_INVALID_REQUEST
            );
            throw $exception->addErrorDetails($errors);
        }

        try {
            $response = $this->authService->confirmEmail($token);

        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_CONFIRMATION_TOKEN_NOT_EXIST:
                case AbstractService::ERROR_CONFIRMATION_TOKEN_EXPIRED:
                case AbstractService::ERROR_CONFIRMATION_CONFIRMED:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                case AbstractService::ERROR_USER_NOT_FOUND:
                    throw new Http404Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        return $response;
    }

    /**
     * Resend confirmation email
     *
     * @return null
     */
    public function resendConfirmationEmailAction()
    {
        $errors = [];
        $token = trim($this->request->getPost('token'));

        if (empty($token)) {
            $errors['token'] = 'Missing token';
        }

        if ($errors) {
            $exception = new Http400Exception(
                'Input parameters validation error',
                self::ERROR_INVALID_REQUEST
            );
            throw $exception->addErrorDetails($errors);
        }

        try {
            $this->authService->resendConfirmationEmail($token);

        } catch (ServiceException $e) {
            switch ($e->getCode()) {
                case AbstractService::ERROR_CONFIRMATION_TOKEN_NOT_EXIST:
                    throw new Http422Exception($e->getMessage(), $e->getCode(), $e);
                case AbstractService::ERROR_USER_NOT_FOUND:
                    throw new Http404Exception($e->getMessage(), $e->getCode(), $e);
                default:
                    throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
            }
        }

        return null;
    }

}
