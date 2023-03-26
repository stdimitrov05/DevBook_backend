<?php

namespace App\Services;

use App\Exceptions\HttpExceptions\Http403Exception;
use App\Exceptions\ServiceException;
use App\Lib\Helper;
use App\Models\Users;
use Intervention\Image\ImageManager;

/**
 *
 * @UsersService
 * @\App\Services\UsersService
 * @uses \App\Services\AbstractService
 */
class UsersService extends AbstractService
{
    /**
     * Creates a new user with the provided data and returns an array with the new user's ID.
     * @param array $data An array containing the data for the new user.
     * @return array An array containing the new user's ID.
     * @throws ServiceException If the new user could not be created.
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
