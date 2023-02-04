<?php

namespace App\Services;

use App\Exceptions\HttpExceptions\Http403Exception;
use App\Exceptions\ServiceException;
use App\Lib\Helper;
use App\Models\EmailConfirmations;
use App\Models\LoginsHistory;
use App\Models\ResetPasswords;
use App\Models\Users;
use Phalcon\Db\Column;
use App\Models\LoginsFailed;
use Firebase\JWT\JWT;

class AuthService extends AbstractService
{

    /**
     * Check the user credentials
     *
     * @param array $credentials
     * @return array
     * @throws ServiceException
     */
    public function checkLogin($credentials)
    {
        // Check if the user exist by email or username
        $email = strtolower($credentials['email']);

        $user = Users::findFirst(
            [
                'conditions' => 'email = :email: OR username = :username:',
                'bind' => [
                    'email' => $email,
                    'username' => $email
                ],
                'bindTypes'  => [
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
        if (!$this->security->checkHash($credentials['password'], $user->password)) {
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

        // Authenticate user by generating JWT tokens
        $tokens = $this->authenticateUser($user, $credentials);

        return $tokens;
    }

    /**
     * Authenticates the user
     *
     * @param Users $user
     * @param array $credentials
     *
     * @return array
     */
    public function authenticateUser(Users $user, array $credentials)
    {
        $tokens = $this->generateJwtTokens($user, $credentials['remember']);

        // Save user tokens: jti of jwt refresh and fcm
        $login = new LoginsHistory();
        $login->user_id = $user->id;
        $login->jti = $tokens['jti'];

        if (isset($credentials['fcmToken']) && ! empty($credentials['fcmToken'])) {
            $login->fcm_token = $credentials['fcmToken'];
        }

        $clientIpAddress = $this->request->getClientAddress();
        $userAgent = $this->request->getUserAgent();

        $login->user_agent = empty($userAgent) ? null : substr( $userAgent,0,250);
        $login->ip_address = empty($clientIpAddress) ? null : $clientIpAddress;
        $login->expire_at = $tokens['expireAt'];
        $login->save();

        return [
            'accessToken' => $tokens['accessToken'],
            'refreshToken' => $tokens['refreshToken']
        ];
    }

    /**
     * Generate JWT access and refresh tokens
     *
     * @param Users $user
     * @param int $remember
     *
     * @return array
     */
    private function generateJwtTokens($user, $remember)
    {
        $key = base64_decode($this->config->auth->key);
        $issuedAt = time();

        $accessPayload = [
            'iat' => $issuedAt, // Issued at: time when the token was generated
            'iss' => $this->config->application->domain, // Issuer
            'nbf' => $issuedAt, // Not before
            'exp' => $issuedAt + $this->config->auth->accessTokenExpire, // Expire
            'userId' => $user->id
        ];

        // Longer expiration time if user click remember me
        $refreshExpire = $remember == 1
            ? $this->config->auth->refreshTokenRememberExpire
            : $this->config->Fauth->refreshTokenExpire;

        // Generate jti
        $jti = base64_encode(openssl_random_pseudo_bytes(32));

        $refreshPayload = [
            'iat' => $issuedAt, // Issued at: time when the token was generated
            'iss' => $this->config->application->domain, // Issuer
            'jti' => $jti, // Json Token Id -> an unique identifier for the token
            'nbf' => $issuedAt, // Not before
            'exp' => $issuedAt + $refreshExpire, // Expire
            'userId' => $user->id
        ];

        return [
            'accessToken' => JWT::encode($accessPayload, $key, 'HS512'),
            'refreshToken' => JWT::encode($refreshPayload, $key, 'HS512'),
            'expireAt' => $issuedAt + $refreshExpire,
            'jti' => $jti,
        ];

    }

    /**
     * Regenerate access and refresh tokens
     *
     * @return array $jwt
     */
    public function refreshJwtTokens()
    {
        $tokens = [];

        try {
            $key = base64_decode($this->config->auth->key);
            $jwt = $this->getBearerToken();

            if ($jwt === false) {
                throw new ServiceException(
                    'Missing token',
                    self::ERROR_MISSING_TOKEN
                );
            }

            $jwtDecoded = JWT::decode($jwt, $key, ['HS512']);

            // Check if user exists and get data for access token
            $userToken = LoginsHistory::findFirst(
                [
                    'conditions' => 'jti = :jti: AND user_id = :userId:',
                    'order' => 'created_at DESC',
                    'bind' => [
                        'jti' => $jwtDecoded->jti,
                        'userId' => $jwtDaecoded->userId
                    ],
                    'limit' => 1
                ]
            );

            if (!$userToken) {
                throw new ServiceException(
                    "User not found",
                    self::ERROR_USER_NOT_FOUND
                );
            }

            if ($jwtDecoded) {
                // Check if the refresh token is blacklisted
                if ($userToken->user->banned) {
                    throw new ServiceException(
                        'Blacklisted token',
                        self::ERROR_BAD_TOKEN
                    );
                }

                // If refresh token length is greater than 5 days (remember is true)
                $remember = ($jwtDecoded->exp - $jwtDecoded->nbf > 432000) ? 1 : 0;
                $tokens = $this->generateJwtTokens($userToken->user, $remember);

                $userToken->expire_at = $tokens['expireAt'];
                $userToken->jti = $tokens['jti'];
                $userToken->update();
            }

        } catch (\Exception $e) {
            throw new Http403Exception($e->getMessage(), self::ERROR_BAD_TOKEN, $e);
        }

        return [
            'accessToken' => $tokens['accessToken'],
            'refreshToken' => $tokens['refreshToken']
        ];
    }

    /**
     * Verify JWT token
     *
     * @return string $jwt
     */
    public function verifyToken()
    {
        try {
            $key = base64_decode($this->config->auth->key);
            $jwt = $this->getBearerToken();

            JWT::decode($jwt, $key, ['HS512']);

        } catch (\Exception $e) {
            throw new ServiceException('Bad token', self::ERROR_BAD_TOKEN);
        }

        return true;
    }

    /**
     * Get authorization header
     *
     * @return mixed
     */
    private function getBearerToken()
    {
        $authorizationHeader = $this->request->getHeader('Authorization');

        if ($authorizationHeader AND preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        } else {
            return false;
        }
    }

    /**
     * Check if the JWT token exists in Redis
     *
     * @param string $jti
     * @return boolean
     */
    private function checkJwtInRedis($jti)
    {
        $key = 'jwt:' . $jti;
        $redisData = $this->redis->get($key);

        if ($redisData) {
            return true;
        }

        return false;
    }

    /**
     * Implements login throttling
     * Reduces the effectiveness of brute force attacks
     *
     * @param int $userId
     */
    private function registerUserThrottling($userId)
    {
        $failedLogin = new LoginsFailed();
        $failedLogin->user_id = $userId;
        $clientIpAddress = $this->request->getClientAddress();
        $userAgent = $this->request->getUserAgent();

        $failedLogin->ip_address = empty($clientIpAddress) ? null : $clientIpAddress;
        $failedLogin->user_agent = empty($userAgent) ? null : substr( $userAgent,0,250);
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

        if ($user->banned != 0) {
            throw new ServiceException(
                'The user is banned',
                self::ERROR_USER_BANNED
            );
        }

    }

    /**
     * Send reset password link
     *
     * @param string $email
     * @return array
     * @throws ServiceException
     */
    public function forgotPassword($email)
    {
        try {
            // Get user settings
            $user = Users::findFirstByEmail($email);

            if ($user) {
                $clientIpAddress = $this->request->getClientAddress();
                $userAgent = $this->request->getUserAgent();

                $resetPassword = new ResetPasswords();
                $resetPassword->user_id = $user->id;
                $resetPassword->ip_address = empty($clientIpAddress) ? null : $clientIpAddress;
                $resetPassword->user_agent = empty($userAgent) ? null : substr($userAgent, 0, 250);
                $resetPassword->save();
            }

        } catch (\Exception $e) {
            throw new Http403Exception($e->getMessage(), self::ERROR_BAD_TOKEN, $e);
        }

        return null;
    }

    /**
     * Check reset password link token
     *
     * @param string  $token
     * @return array
     * @throws ServiceException
     */
    public function verifyResetPasswordToken($token)
    {
        $resetPassword = ResetPasswords::findFirstByCode($token);

        if ($resetPassword === false) {
            throw new ServiceException(
                'Sorry, your password reset link is not valid!',
                self::ERROR_RESET_TOKEN_NOT_EXIST
            );
        }

        $expiredToken = time() - $resetPassword->created_at > 8*60*60;

        if ($resetPassword->reset != 0 || $expiredToken === true) {
            throw new ServiceException(
                'Sorry, your password reset link has expired!',
                self::ERROR_RESET_TOKEN_EXPIRED
            );
        }

        return ['token' => $token];
    }

    /**
     * Change user password from reset password link
     *
     * @param array $data
     */
    public function changePassword($data)
    {
        try {
            // First check the token in ResetPasswords table
            $resetPassword = ResetPasswords::findFirstByCode($data['token']);

            if ($resetPassword === false) {
                throw new ServiceException(
                    'Reset token doesn\'t exist',
                    self::ERROR_RESET_TOKEN_NOT_EXIST
                );
            }

            $expiredToken = time() - $resetPassword->created_at > 8*60*60;

            if ($resetPassword->reset != 0 || $expiredToken === true) {
                throw new ServiceException(
                    'Reset token has expired',
                    self::ERROR_RESET_TOKEN_EXPIRED
                );
            }

            // Find user that matches the token
            $user = Users::findById($resetPassword->user_id);

            if (!$user) {
                throw new ServiceException(
                    "User not found",
                    self::ERROR_USER_NOT_FOUND
                );
            }

            // Change user password
            $user->password = $this->security->hash($data['password']);
            $result = $user->update();

            if (!$result) {
                throw new ServiceException(
                    'Unable to update user',
                    self::ERROR_UNABLE_UPDATE_USER
                );
            }

            // Make current reset token invalid
            $resetPassword->reset = 1;
            $resetPassword->save();

        } catch (\PDOException $e) {
            throw new ServiceException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get identity info
     *
     * @return mixed
     */
    public function getIdentity()
    {
        $response = false;

        try {
            $key = base64_decode($this->config->auth->key);
            $jwt = $this->getBearerToken();

            if ($jwt) {
                $response = JWT::decode($jwt, $key, ['HS512'])->userId;
            }

        } catch (\Exception $e) {
            throw new ServiceException('Bad token', self::ERROR_BAD_TOKEN, $e);
        }

        return $response;
    }

    /**
     * Check if user is authorized for the action
     *
     * @param integer $userId
     * @return integer
     */
    public function isAuthorized($userId)
    {
        // If logged user is not the boat owner throw exception
        $loggedUser = $this->authService->getIdentity();

        if (($loggedUser === false) || ($loggedUser != $userId)) {
            throw new ServiceException(
                'Not authorized',
                AbstractService::ERROR_USER_NOT_AUTHORIZED
            );
        }

        return $loggedUser;
    }

    /**
     * Confirm user email
     *
     * @param string $token
     * @return array
     */
    public function confirmEmail($token)
    {
        try {
            // First check the token in ResetPasswords table
            $emailConfirmation = EmailConfirmations::findFirstByToken($token);

            if (empty($emailConfirmation)) {
                throw new ServiceException(
                    'Confirmation token is not valid.',
                    self::ERROR_CONFIRMATION_TOKEN_NOT_EXIST
                );
            }

            $expiredToken = time() - $emailConfirmation->created_at > 24*60*60;

            // Is the token expired
            if ($expiredToken === true) {
                throw new ServiceException(
                    'Confirmation token has expired.',
                    self::ERROR_CONFIRMATION_TOKEN_EXPIRED
                );
            }

            // Is email already confirmed
            if ($emailConfirmation->confirmed != 0) {
                throw new ServiceException(
                    'Your email is already confirmed.',
                    self::ERROR_CONFIRMATION_CONFIRMED
                );
            }

            // Find user that matches the token
            $user = Users::findById($emailConfirmation->user_id);

            if (!$user) {
                throw new ServiceException(
                    "User not found",
                    self::ERROR_USER_NOT_FOUND
                );
            }

            // Change user to active
            $user->active = 1;
            $user->update();

            // Make current confirm token invalid
            $emailConfirmation->confirmed = 1;
            $emailConfirmation->save();

            // Logs the user in
            $tokens = $this->authenticateUser($user, ['remember' => 1]);

        } catch (\PDOException $e) {
            throw new ServiceException($e->getMessage(), $e->getCode(), $e);
        }

        return $tokens;
    }

    /**
     * Resend confirmation email
     *
     * @param string $token
     */
    public function resendConfirmationEmail($token)
    {
        try {
            // First check the token in ResetPasswords table
            $emailConfirmation = EmailConfirmations::findFirstByToken($token);

            if (empty($emailConfirmation)) {
                throw new ServiceException(
                    'Confirmation token is not valid.',
                    self::ERROR_CONFIRMATION_TOKEN_NOT_EXIST
                );
            }

            // Find user that matches the token
            $user = Users::findById($emailConfirmation->user_id);

            if (!$user) {
                throw new ServiceException(
                    "User not found",
                    self::ERROR_USER_NOT_FOUND
                );
            }

            $ipAddress = $this->request->getClientAddress();
            $userAgent = $this->request->getUserAgent();
            $token = Helper::generateToken();

            // Send email with confirmation link
            $newEmailConfirmation = new EmailConfirmations();
            $newEmailConfirmation->user_id = $user->id;
            $newEmailConfirmation->token = $token;
            if ($ipAddress) $newEmailConfirmation->ip_address = $ipAddress;
            if ($userAgent) $newEmailConfirmation->user_agent = $userAgent;
            $newEmailConfirmation->save();

        } catch (\PDOException $e) {
            throw new ServiceException($e->getMessage(), $e->getCode(), $e);
        }
    }

}
