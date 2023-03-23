<?php

namespace App\Services;

use App\Exceptions\HttpExceptions\Http403Exception;
use App\Exceptions\ServiceException;
use App\Lib\Helper;
use App\Models\Avatars;
use App\Models\EmailConfirmations;
use App\Models\UserBillings;
use App\Models\Users;
use Intervention\Image\ImageManager;

/**
 * Business-logic for site frontend
 *
 * @UsersService
 * @\App\Services\UsersService
 * @uses \App\Services\AbstractService
 */
class UsersService extends AbstractService
{
    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $ip = $this->request->getClientAddress();
        $ip = empty($ip) ? $ip = "25.63.116.221" : $ip;
        $encodedIp = Helper::hashIpAddressToVarbinary($ip);
        $user = new Users();
        $user->ip_address = $encodedIp;
        $user->assign($data);
        $isCreated = $user->create();

        if ($isCreated !== true) {
            throw new ServiceException(
                'Unable to create user',
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        return [
            "userId" => $user->id
        ];

    }

}
