<?php

namespace App\Services;

use App\Exceptions\ServiceException;

/**
 * @RedisService
 * @\App\Services\RedisService
 */
class RedisService extends AbstractService
{
    /**
     * Store JWT refresh token jti in redis set
     * @param int $userId
     * @param string $jti
     * @param int $expire
     * @return null
     * @throws \RedisException
     */
    public function storeJti(int $userId, string $jti, int $expire)
    {
        $redis = $this->redis;

        $setName = $this->config->redis->usersPrefix . $userId . $this->config->redis->jtiPostfix;
        $wlJti = $this->config->redis->whiteListPrefix . $jti;

        $transaction = $redis->multi()
            ->sAdd($setName, $jti)               // Store jti in set
            ->set($wlJti, 1)             // Whitelist refresh token (wl_jti.... => 1)
            ->expire($wlJti, $expire)            // Redis key expire when JWT expire
            ->exec();

        if (in_array(false, $transaction)) {
            throw new ServiceException(
                "Unable to store in Redis",
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        return null;
    }

    /**
     * Remove JTI from white list and from user set
     * @param string $jti
     * @param int $userId
     * @retrun null
     * @throws \RedisException
     */

    public function removeJti(string $jti, int $userId)
    {
        $setKey = $this->config->redis->usersPrefix . $userId . $this->config->redis->jtiPostfix;
        $wlJtiKey = $this->config->redis->whiteListPrefix . $jti;

        $transaction = $this->redis->multi()
            ->sRem($setKey, $jti)       // Remove JTI from user set
            ->del($wlJtiKey)            // Remove JTI from white list
            ->exec();

        if (in_array(false, $transaction)) {
            throw new ServiceException(
                "Unable to delete from Redis",
                self::ERROR_UNABLE_TO_DELETE
            );
        }

        return null;
    }

    /**
     * clearUserJtis
     * @param int $userId
     */
    public function clearJwtByUserId(int $userId): void
    {

        $setKey = $this->config->redis->usersPrefix . $userId . $this->config->redis->jtiPostfix;
        $jtis = $this->redis->sMembers($setKey);

        // Clear whitelist jtis
        foreach ($jtis as $jti) {
            $wlJtiKey = $this->config->redis->whiteListPrefix . $jti;

            $this->redis->del($wlJtiKey);
        }

        // Delete user set
        $this->redis->del($setKey);
    }


    /**
     * Check if JTI is in the white list
     * @param string $jti
     * @throws \RedisException
     */
    public function isJtiInWhiteList(string $jti): void
    {
        $jtiWlKey = $this->config->redis->whiteListPrefix . $jti;
        $jtiInWhiteList = $this->redis->get($jtiWlKey);

        if (!$jtiInWhiteList) {
            throw new ServiceException(
                "Bad token",
                self::ERROR_BAD_TOKEN
            );
        }
    }

    /**
     * Store csrf keys in redis
     * @param string $csrf
     * @param ?string $captcha
     * @throws \RedisException
     */
    public function storeCsrf(string $csrf, ?string $captcha = null): void
    {
        $csrf = $this->config->redis->csrfPrefix . $csrf;
        $expire = 120; # 2 min

        // Store captcha as value or one if missing
        $captcha = empty($captcha) ? 1 : $captcha;

        // Store csrf token key with captcha code value
        $this->redis->set($csrf, $captcha, $expire);
    }

    /**
     * Verify csrf token and captcha in redis
     * @param string $csrf
     * @param ?string $captcha
     * @return bool
     * @throws \RedisException
     */
    public function verifyCsrfAndCaptcha(string $csrf, ?string $captcha = null): bool
    {
        $csrfKey = $this->config->redis->csrfPrefix . $csrf;
        $csrf = $this->redis->get($csrfKey);

        if (!$csrf) return false;

        if (!empty($captcha) && $captcha != $csrf) {
            return false;
        }

        return true;
    }

}
