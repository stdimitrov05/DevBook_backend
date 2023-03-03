<?php

namespace App\Services;

use App\Exceptions\ServiceException;
use App\Lib\Helper;
use App\Models\LoginsFailed;
use App\Models\Users;
use Phalcon\Db\Column;
use Phalcon\Encryption\Security\JWT\Builder;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;
use Phalcon\Encryption\Security\JWT\Signer\Hmac;
use Phalcon\Encryption\Security\JWT\Token\Parser;

/**
 * Business-logic for site frontend
 *
 * @AuthService
 * @\App\Services\AuthService
 * @uses \App\Services\AbstractService
 */
class AuthService extends AbstractService
{
    // Constants
    // Redis names SETS
    private const  usersPrefix = 'users:';
    private const  jtiPostfix = ':jtis';
    private const  whiteListPrefix = 'wl_';


    /**
     * @param array $data
     * @return array
     * @throws ValidatorException
     */
    public function login(array $data): array
    {
        // Get email or username and convert to small letters
        $email = strtolower($data['email']);

        // Search with $email (email or username) current user
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

        // If user is not found
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

        // If user profile has deleted
        if (!empty($user->deleted_at)) {
            $this->registerUserThrottling(0);
            throw new ServiceException(
                'Wrong email or password',
                self::ERROR_WRONG_EMAIL_OR_PASSWORD
            );
        }

        // Check if the user was flagged
        $this->checkUserFlags($user);

        // Generate JWT
        $jwtTokens = $this->generateJwtTokens($user->id, $data['remember']);

        return [
            'accessToken' => $jwtTokens['accessToken'],
            'refreshToken' => $jwtTokens['refreshToken']
        ];

    }

    /**
     * refreshJwtTokens
     * @retrun array
     */
    public function refreshJwtTokens(): array
    {
        $newTokens = [];

        // Get jwt (refreshToken) from headers
        $jwt = $this->getJwtToken();

        // If jwt (refreshToken) is missing
        if (!$jwt) {
            throw  new ServiceException(
                'Jwt token is not found',
                self::ERROR_JWT_IS_NOT_FOUND
            );
        }

        // Decode JWT (refreshToken) and getClaims
        $decodedJWT = $this->decodeJWT($jwt);
        // From decodedJWT get jti
        $jti = $decodedJWT->getPayload()['jti'];
        // From decodedJwt get sub (userId)
        $userId = $decodedJWT->getPayload()['sub'];
        // From decodedJwt get expired
        $exp = $decodedJWT->getPayload()['exp'];

        // Is expired
        if ($exp < time()) {
            throw  new ServiceException(
                'Token has expired',
                self::ERROR_HAS_EXPIRED
            );
        }

        // Check jwt (refreshToken) in redis
        $jwtIsExist = $this->checkJwtById($userId, $jti);

        if ($jwtIsExist === false) {
            throw  new ServiceException(
                'Token is not found',
                self::ERROR_JWT_IS_NOT_FOUND
            );
        }

        // Remove jti form redis (in SETS and wl_$jti)
        $isRemove = $this->removeJtiInRedis($jti, $userId);

        if ($isRemove !== null) {
            throw  new ServiceException(
                'Token can`t remove in redis',
                self::ERROR_JWT_CANT_REMOVE
            );
        }

        $generateToken = $this->generateJwtTokens($userId);

        $newTokens = [
            'accessToken' => $generateToken['accessToken'],
            'refreshToken' => $generateToken['refreshToken'],
        ];


        return $newTokens;

    }

    /**
     * Decode JWT tokens
     * @params string $token
     * @retrun  object
     * 'exp' => int 1680179383 -> $iat + $this->config->auth->accessTokenExpire
     * 'jti' => string 'dCTf6o3na2Ut3ncYu4ZQ6vEL2Kylt7fVDtAWNGfluWw=' -> setID
     * 'iss' => string 'domain' -> $this->config->application->domain
     * 'iat' => int 1677587383  -> time()
     * 'sub' => string '1'  -> UserID
     */
    public function decodeJWT(string $token): object
    {
        // Parse the token
        $parser = new Parser();

        return $parser->parse($token)->getClaims();
    }

    /**
     * forgotPassword
     * @param string $email
     * @retrun array
     */
    public function forgotPassword(string $email): array
    {
        $this->db->begin();
        // Check email
        $user = Users::findFirstByEmail($email);

        if (!$user) {
            throw  new ServiceException(
                "Sending email...",
                self::ERROR_IS_NOT_FOUND
            );
        }

        // Check user flags
        $this->checkUserFlags($user);

        // Generate confirmToken
        $confirmToken  = Helper::generateToken();



        var_dump($confirmToken);die;
    }


    /**
     * Get authorization header
     * @return false|string
     */
    public function getJwtToken(): bool|string
    {
        // Get jwt (refreshToken) from headers
        $authorizationHeader = $this->request->getHeader('Authorization');

        if ($authorizationHeader and preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        } else {
            return false;
        }
    }

    /**
     * generateJwtTokens
     * Generate access and refresh jwt tokens
     * @param int $userId
     * @param int $remember
     * @return array
     * @throws ValidatorException
     * @retrun  array
     */
    private function generateJwtTokens(int $userId, int $remember = 0): array
    {
        // Generate jti
        $jti = base64_encode(openssl_random_pseudo_bytes(32));
        // Defaults to 'sha512'
        $signer = new Hmac('sha512');
        $iat = time();
        $iss = $this->config->application->domain;
        $exp = $iat + $this->config->auth->accessTokenExpire;

        // Create accessToken with expire 2 minutes
        $accessToken = (new Builder($signer))
            ->setExpirationTime($exp)
            ->setPassphrase($this->config->auth->key)
            ->setNotBefore($iat)
            ->setSubject($userId)
            ->setIssuer($iss)
            ->setIssuedAt($iat)
            // Get build string with all parts
            ->getToken()
            // Get current jwt (accessToken)
            ->getToken();

        // Longer expiration time if user click remember me
        // Set refresh token life :  1 week or 30 days
        $refreshExpire = $remember == 1
            ? $this->config->auth->refreshTokenRememberExpire
            : $this->config->auth->refreshTokenExpire;

        // Create refreshToken
        $refreshToken = (new Builder($signer))
            ->setExpirationTime($iat + $refreshExpire)
            ->setPassphrase($this->config->auth->key)
            ->setNotBefore($iat)
            ->setId($jti)
            ->setIssuer($iss)
            ->setIssuedAt($iat)
            ->setSubject($userId)
            // Get build string with all parts
            ->getToken()
            // Get current jwt (accessToken)
            ->getToken();

        // If accessToken or refreshToken has not created
        if (!$accessToken || !$refreshToken) {
            throw  new ServiceException(
                'Jwt tokens can`t create',
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        // Set jwt tokens in redis
        $isInRedis = $this->setJtiInRedis($userId, $jti, $iat + $refreshExpire);

        if ($isInRedis !== null) {
            throw  new ServiceException(
                'Unable to save jti',
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        return [
            "accessToken" => $accessToken,
            "refreshToken" => $refreshToken,
            'expireAt' => $iat + $refreshExpire,
        ];

    }


    /**
     * setJtiInRedis
     * Set sets in  users: $userId : tokens on redis
     * @param int $userId
     * @param string $jti
     * @param int $expire
     * @return null
     */
    private function setJtiInRedis(int $userId, string $jti, int $expire)
    {
        $redis = $this->redis;

        $setsName = self::usersPrefix . $userId . self::jtiPostfix;
        (string)$wl = self::whiteListPrefix . $jti;

        // Store jti in redis SETS
        $redis->sAdd($setsName, $jti);
        $redis->expire($setsName, $expire + 60);
        // Whitelist refresh token jti
        $redis->set($wl, 1);
        $redis->expire($wl, $expire + 60);


        return null;
    }

    /**
     * removeJtiInRedis
     * @description  Remove from users:$userId:jti and remove to whitelist
     * @param string $jti
     * @param int $userId
     * @retrun null
     */

    private function removeJtiInRedis(string $jti, int $userId)
    {
        $redis = $this->redis;

        $setsName = self::usersPrefix . $userId . self::jtiPostfix;
        (string)$wl = self::whiteListPrefix . $jti;

        // Set in sets jwt
        $redis->SREM($setsName, $jti);
        // WhiteList refreshToken
        $redis->del($wl);

        return null;
    }


    /**
     * checkJwtById
     * @description  Check jwt (refreshToken id ) in redis
     * @param string $jti
     * @param int $userId
     * @retrun bool
     */
    private function checkJwtById(int $userId, string $jti): bool
    {
        (bool)$isExist = false;

        $redis = $this->redis;
        // Call whitelist : wl_$jti
        (string)$wl = self::whiteListPrefix . $jti;
        // Call SETS : users:$userId:jti
        (string)$setsName = self::usersPrefix . $userId . self::jtiPostfix;
        // Check jti (refreshToken id) in redis
        $setsJti = $redis->SISMEMBER($setsName, $jti);
        $isWl = $redis->get($wl);

        $isWl == 1 && $setsJti == true ? ($isExist = true) : $isExist;

        return $isExist;

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
    * emailConfirm
     * @param string $param
     * @return  array
     */
    public function emailConfirm(string $param) : array
    {


    }


}
