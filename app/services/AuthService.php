<?php

namespace App\Services;

use App\Exceptions\HttpExceptions\Http403Exception;
use App\Exceptions\ServiceException;
use App\Lib\Captcha;
use App\Lib\Helper;
use App\Models\LoginsFailed;
use App\Models\Users;
use Phalcon\Db\Column;
use Phalcon\Encryption\Security\Exception;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;

/**
 * Business-logic for site frontend
 *
 * @AuthService
 * @\App\Services\AuthService
 * @uses \App\Services\AbstractService
 */
class AuthService extends AbstractService
{
    /**
     * Generates a new captcha and form keys and stores them in Redis.
     * @return array An array containing the generated CSRF token and captcha image.
     * @throws Exception
     * @throws \Exception
     */
    public function getCaptcha(): array
    {
        // Generate captcha and form keys
        $csrf = $this->security->getToken();

        $captcha = new Captcha();
        $captcha->setCode(strtoupper(Helper::randomKeys()));

        // Store in redis
        $this->redisService->storeCsrf($csrf, $captcha->getCode());

        return [
            "csrf" => $csrf,
            "captcha" => $captcha->load()
        ];
    }

    /**
     * Verifies the given CSRF token and captcha code against the values stored in Redis.
     *
     * @param string $csrf The CSRF token to verify.
     * @param string|null $captcha The captcha code to verify. Can be null if captcha is not required.
     * @return bool Returns true if both the CSRF token and captcha code are valid, false otherwise.
     * @throws \RedisException
     */
    public function verifyCsrfAndCaptcha(string $csrf, ?string $captcha = null): bool
    {
        return $this->redisService->verifyCsrfAndCaptcha($csrf, $captcha);
    }

//    /**
//     * Verify JWT token
//     *
//     * @return bool
//     * @throws ValidatorException
//     */
//    public function verifyToken(): bool
//    {
//        // Get JWT refresh token from headers
//        $jwt = $this->jwt->getAuthorizationToken();
//        $token = $this->jwt->decode($jwt);
//        $this->jwt->validateJwt($token);
//        return true;
//    }

}
