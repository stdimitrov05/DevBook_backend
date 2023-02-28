<?php

namespace App\Services;

use App\Exceptions\ServiceException;
use App\Models\LoginsFailed;
use App\Models\Users;
use Phalcon\Db\Column;
use Phalcon\Encryption\Security\JWT\Builder;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;
use Phalcon\Encryption\Security\JWT\Signer\Hmac;
use Firebase\JWT\JWT;

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
     * @param array $data
     * @return array
     * @throws ValidatorException
     */
    public function login(array $data): array
    {
        $email = strtolower($data['email']);

        $user = Users::findFirst(
            [
                'conditions' => 'email = :email: OR username = :username:',
                'bind' => [
                    'email' => $email,
                    'username' => $email
                ],
                'bindTypes' => [
                    Column::BIND_PARAM_STR,
                    Column::BIND_PARAM_STR
                ],
            ]
        );

        if (!$user) {
            $this->registerUserThrottling(0);
            throw new ServiceException(
                'Wrong email or password',
                self::ERROR_WRONG_EMAIL_OR_PASSWORD
            );
        }

        // Check the password
        if (!$this->security->checkHash($data['password'], $user->password)) {
            $this->registerUserThrottling($user->id);
            throw new ServiceException(
                'Wrong email or password',
                self::ERROR_WRONG_EMAIL_OR_PASSWORD
            );
        }

        if (!empty($user->deleted_at)) {
            $this->registerUserThrottling(0);
            throw new ServiceException(
                'Wrong email or password',
                self::ERROR_WRONG_EMAIL_OR_PASSWORD
            );
        }

        // Check if the user was flagged
        $this->checkUserFlags($user);


//        Generate JWT
        $tokens = $this->generateJWT($user->id, $data['remember']);

        if (!$tokens) {
            throw new ServiceException(
                "Unable to create jwt tokens",
                self::ERROR_NOT_EXISTS
            );
        }

//        Save into redis data

        $redis = $this->setJWTInRedis($user->id, $tokens);

        if ($redis !== null) {
            throw  new ServiceException(
                "Unable to set redis data",
                self::ERROR_NOT_EXISTS
            );
        }

        return [
            'accessToken' => $tokens['accessToken'],
            'refreshToken' => $tokens['refreshToken']
        ];

    }

    /**
     * refreshJwtTokens
     * @retrun array
     */
    public function refreshJwtTokens()
    {
        $jwt = $this->getBearerToken();
        $key = base64_decode($this->config->auth->key);


        if (!$jwt) {
            throw  new ServiceException(
                'Refresh token is not found',
                self::ERROR_NOT_EXISTS
            );
        }


//        JWT::decode($jwt, $key, ['HS512']);
        var_dump(JWT::decode($jwt, $key, ['HS512']));
        die;

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
                self::ERROR_NOT_EXISTS
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
        (array)$result = json_decode($getData, true);

        return !$result ? [] : $result;
    }

    /**
     * setJWTInRedis
     * @param int $userId
     * @param array $tokens
     * @retrun null
     */
    private function setJWTInRedis(int $userId, array $tokens)
    {
        $redis = $this->di->get('redis');

        $data = [
            'jti' => $tokens['jti'],
            'expireAt' => $tokens['expireAt'],
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


    /**
     * Implements login throttling
     * Reduces the effectiveness of brute force attacks
     *
     * @param int $userId
     */
    private function registerUserThrottling(int $userId)
    {
        $failedLogin = new LoginsFailed();
        $failedLogin->user_id = $userId;
        $clientIpAddress = $this->request->getClientAddress();
        $userAgent = $this->request->getUserAgent();

        $failedLogin->ip_address = empty($clientIpAddress) ? null : $clientIpAddress;
        $failedLogin->user_agent = empty($userAgent) ? null : substr($userAgent, 0, 250);
        $failedLogin->attempted = time();
        $failedLogin->save();

        $attempts = LoginsFailed::count([
            'ip_address = ?0 AND attempted >= ?1',
            'bind' => [
                $this->request->getClientAddress(),
                time() - 3600 * 6 // 6 minutes
            ]
        ]);

        switch ($attempts) {
            case 1:
            case 2:
                // no delay
                break;
            case 3:
            case 4:
                sleep(2);
                break;
            default:
                sleep(4);
                break;
        }
    }

    /**
     * Checks if the user is banned/inactive/suspended
     *
     * @param \App\Models\Users $user
     * @throws ServiceException
     */
    private function checkUserFlags(Users $user)
    {
        if ($user->active != 1) {
            throw new ServiceException(
                'The user is inactive',
                self::ERROR_USER_NOT_ACTIVE
            );
        }
    }


    /**
     * Get authorization header
     *
     * @return mixed
     */
    private function getBearerToken()
    {
        $authorizationHeader = $this->request->getHeader('Authorization');

        if ($authorizationHeader and preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        } else {
            return false;
        }
    }
}
