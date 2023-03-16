<?php

namespace App\Services;

use App\Exceptions\HttpExceptions\Http403Exception;
use App\Exceptions\ServiceException;
use App\Lib\Helper;
use App\Models\EmailConfirmations;
use App\Models\ForgotPassword;
use App\Models\LoginsFailed;
use App\Models\Users;
use mysql_xdevapi\Exception;
use Phalcon\Db\Column;
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
     * @param array $data
     * @return array
     * @throws ValidatorException
     * @throws \RedisException
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


        // Check if the user was flagged
        $this->checkUserFlags($user);

        // Generate JWT
        $jwtTokens = $this->jwt->generateTokens($user->id, $data['remember']);

        return [
            'accessToken' => $jwtTokens['accessToken'],
            'refreshToken' => $jwtTokens['refreshToken']
        ];

    }

    /**
     * refreshJwtTokens
     * @retrun array
     * @throws ValidatorException
     * @throws \RedisException
     */
    public function refreshJwtTokens(): array
    {
        // Get JWT refresh token from headers
        $jwt = $this->jwt->getAuthorizationToken();
        $token = $this->jwt->decode($jwt);
        $userId = $this->userId();

        $this->jwt->validateJwt($token);

        // Check if jti is in the white list (redis)
        $jti = $token->getClaims()->getPayload()['jti'];
        $this->redisService->isJtiInWhiteList($jti);

        $this->redisService->removeJti($jti, $userId);

        $tokenExpire = $token->getClaims()->getPayload()['exp'] - $token->getClaims()->getPayload()['nbf'];
        $remember = $tokenExpire > $this->config->auth->refreshTokenExpire ? 1 : 0;

        $newTokens = $this->jwt->generateTokens($userId, $remember);

        return [
            'accessToken' => $newTokens['accessToken'],
            'refreshToken' => $newTokens['refreshToken'],
        ];
    }

    /**
     * Get user ID
     * @retrun int
     * */
    public function userId(): int
    {
        $jwtToken = $this->jwt->getAuthorizationToken();
        return (int)$this->jwt->decode($jwtToken)->getClaims()->getPayload()['sub'];
    }


    /**
     * Verify token
     *
     * @retrun bool
     *
     *
     * @throws ValidatorException
     */
    public function verifyToken(): bool
    {
        $jwt = $this->jwt->getAuthorizationToken();
        $token = $this->jwt->decode($jwt);
        $this->jwt->validateJwt($token);
        return true;
    }

    /**
     * forgotPassword
     * @param string $email
     * @retrun null
     */
    public function forgotPassword(string $email)
    {
        $clientIpAddress = $this->request->getClientAddress();
        $userAgent = $this->request->getUserAgent();

        // Check email
        $user = Users::findFirstByEmail($email);

        if ($user) {
            // Check user flags
            $this->checkUserFlags($user);

            // Generate confirmToken
            $confirmToken = Helper::generateToken();

            $forgotPassword = new ForgotPassword();
            $forgotPassword->user_id = $user->id;
            $forgotPassword->token = $confirmToken;
            $forgotPassword->ip_address = empty($clientIpAddress) ? null : $clientIpAddress;
            $forgotPassword->user_agent = empty($userAgent) ? null : substr($userAgent, 0, 250);
            $created = $forgotPassword->create();

            if (!$created) {
                throw  new ServiceException(
                    "Unable to create token",
                    self::ERROR_UNABLE_TO_CREATE
                );
            }

            // Send email forgot password >>>
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
    public function checkUserFlags(Users $user)
    {
        if ($user->deleted_at != null) {
            throw new ServiceException(
                'The user is deleted',
                self::ERROR_ACCOUNT_DELETED
            );
        }

        if ($user->active != 1) {
            throw new ServiceException(
                'The user is inactive',
                self::ERROR_USER_NOT_ACTIVE
            );
        }

    }

    /**
     * emailConfirm
     * @param string $token
     * @return  null
     */
    public function emailConfirm(string $token)
    {
        // Start a transaction
        $this->db->begin();

        try {
            // Found current token
            $email = EmailConfirmations::findFirstByToken($token);
            // Check
            if (!$email) {
                throw  new ServiceException(
                    "Token is not found",
                    self::ERROR_NOT_FOUND
                );
            } elseif ($email->confirmed === 1) {
                throw  new ServiceException(
                    "Token is confirmed",
                    self::ERROR_TOKEN_HAS_CONFIRMED
                );
            }
            // Update current token as confirmed
            $email->confirmed = 1;
            $email->update();
            // Update in users table column activate
            $user = Users::findFirst([
                'conditions' => 'id = :id:',
                'bind' => ['id' => $email->user_id]
            ]);

            $user->active = 1;
            $user->update();

            // Update activate in elastic
            $this->elastic->updateUserActivateById($user->id);

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new Http403Exception($e->getMessage(), self::ERROR_BAD_TOKEN, $e);
        }
        return null;

    }

    /**
     * resendEmailConfrim
     * @param string $token
     * @retrun  null
     */
    public function resendEmailConfirm(string $token)
    {
        // Get userId form confirm token
        $email = EmailConfirmations::findFirst([
            'conditions' => 'token = :token:',
            'bind' => ['token' => $token]
        ]);

        if (!$email) {
            throw  new ServiceException(
                "User is not found",
                self::ERROR_NOT_FOUND
            );
        }
        // Get user data
        $user = Users::findFirst([
            'conditions' => 'id = :userId:',
            'bind' => ['userId' => $email->user_id]
        ]);

        if (!$user) {
            throw  new ServiceException(
                "User email is not found",
                self::ERROR_NOT_FOUND
            );
        }

        // Generate new confirmToken
        $confirmToken = Helper::generateToken();
        // Send new email with new confrim token
        $this->mailer->confirmEmail($user->email, $user->username, $confirmToken);

        return null;
    }


    /**
     * checkResetToken
     * @param string $token
     * @retrun  array
     */
    public function checkResetToken(string $token): array
    {
        $resetToken = ForgotPassword::findFirstByToken($token);

        if (!$resetToken) {
            throw  new ServiceException(
                "Invalid reset token",
                self::ERROR_BAD_TOKEN
            );
        } elseif ($resetToken->confirmed === 1) {
            throw  new ServiceException(
                "Token is confirmed",
                self::ERROR_TOKEN_HAS_CONFIRMED
            );
        }

        return [
            "token" => $resetToken->token
        ];
    }

    /**
     * changeForgotPassword
     * @param array $data
     * @return  null
     */
    public function changeForgotPassword(array $data)
    {
        try {
            $this->db->begin();
            $userId = ForgotPassword::findFirstByToken($data['token'])->user->id;

            if (!$userId) {
                throw new ServiceException(
                    "User is not found",
                    self::ERROR_NOT_FOUND
                );
            }
            $user = Users::findFirst([
                'conditions' => 'id = :id:',
                'bind' => ['id' => $userId]
            ]);

            // Check the password
            if (!$this->security->checkHash($data['oldPassword'], $user->password)) {
                throw new ServiceException(
                    'Wrong old password',
                    self::ERROR_WRONG_EMAIL_OR_PASSWORD
                );
            }

            // The two passwords do not match
            if ($data['newPassword'] !== $data['currentPassword']) {
                throw new ServiceException(
                    'The two passwords do not match',
                    self::ERROR_WRONG_EMAIL_OR_PASSWORD
                );
            }

            // Hash new password
            $hashPassword = $this->getDI()->getSecurity()->hash($data['newPassword']);

            // Update user password
            $user->password = $hashPassword;
            $user->update();

            $forgotPassword = ForgotPassword::findFirst([
                'conditions' => 'user_id = :userId:',
                'bind' => ['userId' => $user->id]
            ]);

            if ($forgotPassword->confirmed === 1) {
                throw new ServiceException(
                    "Token is confirmed",
                    self::ERROR_TOKEN_HAS_CONFIRMED
                );
            }

            $forgotPassword->confirmed = 1;
            $forgotPassword->update();

            $this->db->commit();
        } catch (Exception $exception) {
            $this->db->rollback();
            throw new \Exception($exception->getMessage());
        }

        return null;

    }


}
