<?php

namespace App\Services;

use App\Exceptions\ServiceException;
use Phalcon\Encryption\Security\JWT\Builder;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;
use Phalcon\Encryption\Security\JWT\Signer\Hmac;

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
     * @return null
     * @throws ValidatorException
     */
    public function index()
    {
        $userId = 5;
        $tokens = $this->generateJWT($userId, 1);

        $redis = $this->di->get('redis');

        (array) $redisData = $this->getRedisDataByUserID($userId);
        var_dump($redisData['jti']);
        die;
        // Define the array values
        $data = [
            'userId' => $userId,
            'jti' => $tokens['jti'],
            'expireAt' => $tokens['expireAt'] // Set the expiration time to one hour from now
        ];

        $redisData = $redis->set('users:' . $userId . ':tokens', json_encode($data));

        if ($redisData === false) {
            throw  new ServiceException(
                'Can`t set data in redis DB',
                self::ERROR_REDIS_NOT_SET_DATA
            );
        }

        return null;
    }

    # Generate JWT tokens

    /**
     * @param int $userId
     * @param int $remember
     * @return array
     * @throws ValidatorException
     * @retrun  array
     */
    private function generateJWT(int $userId, int $remember = 0): array
    {
        // Generate jti
        $jti = base64_encode(openssl_random_pseudo_bytes(32));
        // Defaults to 'sha512'
        $signer = new Hmac();
        $iat = time();
        $iss = $this->config->application->domain;
        $exp = $iat + $this->config->auth->accessTokenExpire;

        $accessToken = (new Builder($signer))
            ->setExpirationTime($exp)
            ->setPassphrase($this->config->auth->key)
            ->setNotBefore($iat)
            ->setSubject($userId)
            ->setIssuer($iss)
            ->setIssuedAt($iat)
            ->getToken()
            ->getToken();

        // Longer expiration time if user click remember me
        $refreshExpire = $remember == 1
            ? $this->config->auth->refreshTokenRememberExpire
            : $this->config->auth->refreshTokenExpire;

        $refreshToken = (new Builder($signer))
            ->setExpirationTime($iat + $refreshExpire)
            ->setPassphrase($this->config->auth->key)
            ->setNotBefore($iat)
            ->setId($jti)
            ->setIssuer($iss)
            ->setIssuedAt($iat)
            ->setSubject($userId)
            ->getToken()
            ->getToken();

        if (!$accessToken || !$refreshToken) {
            throw  new ServiceException(
                'Access or refresh token is not found',
                self::ERROR_ALREADY_EXISTS
            );
        }


        return [
            "accessToken" => $accessToken,
            "refreshToken" => $refreshToken,
            'expireAt' => $iat + $refreshExpire,
            "jti" => $jti
        ];

    }

    /**
     * getRedisData
     * @param int $userId
     * @retrun array
     */
    private function getRedisDataByUserID(int $userId): array
    {
        $redis = $this->di->get('redis');
        (array)$getData = $redis->get('users:' . $userId . ':tokens');
        (array)$response = json_decode($getData, true);

        return !$response ? [] : $response;
    }
}
