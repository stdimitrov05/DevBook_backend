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
     * Get locations, captcha and form random generate token form signUp page
     * @return array
     * @throws Exception
     * @throws \RedisException
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
     * Check form and captcha code in redis
     * @param string $csrf
     * @param ?string $captcha
     * @return bool
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
