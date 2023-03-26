<?php

namespace App\Controllers;

use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use Phalcon\Encryption\Security\Exception;

/**
 * Frontend controller
 */
class FrontendController extends AbstractController
{
    /**
     * Retrieves status information about the API.
     * @return array An array containing the status information.
     *               - status (string): A message indicating that the API is working.
     * @throws Http500Exception If an unexpected error occurs while retrieving the status information.
     */
    public function index(): array
    {
        try {
            $response = $this->frontendService->index();

        } catch (ServiceException $e) {
            throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
        }

        return $response;
    }

    /**
     * Fetches all available locations from the database.
     * @return array An array of location objects, each containing the `id`, `state`, `city`, and `country` properties.
     */
    public function getLocations(): array
    {
        try {
            $response = $this->frontendService->getLocations();

        } catch (ServiceException $e) {
            throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
        }

        return $response;
    }

    /**
     * Generates a new captcha and form keys and stores them in Redis.
     * @return array An array containing the generated CSRF token and captcha image.
     * @throws Exception
     * @throws \Exception
     */
    public function captchaRefresh(): array
    {
        try {
            $response = $this->authService->getCaptcha();

        } catch (ServiceException $e) {
            throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
        }

        return $response;
    }
}
