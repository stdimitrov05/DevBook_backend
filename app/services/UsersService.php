<?php

namespace App\Services;

use App\Lib\Helper;
use App\Models\Avatars;
use App\Models\EmailConfirmations;
use App\Models\Users;
use App\Exceptions\ServiceException;

/**
 * Business-logic for users
 *
 * Class UsersService
 */
class UsersService extends AbstractService
{

    /**
     * Creating a new user
     *
     * @param array $data
     * @return array
     */
    public function createUser(array $data)
    {

        try {
            $this->db->begin();
            $user = new Users();
            $user->assign($data);
            $result = $user->create();

            if (!$result) {
                throw new ServiceException(
                    'Unable to create user',
                    self::ERROR_UNABLE_CREATE_AVATAR
                );
            }

//
//            $ipAddress = $this->request->getClientAddress();
//            $userAgent = $this->request->getUserAgent();
//            $token = Helper::generateToken();

//            // Send email with confirmation link
//            $emailConfirmation = new EmailConfirmations();
//            $emailConfirmation->user_id = $user->id;
//            $emailConfirmation->token = $token;
//            if ($ipAddress) $emailConfirmation->ip_address = $ipAddress;
//            if ($userAgent) $emailConfirmation->user_agent = $userAgent;
//            $emailConfirmation->save();
//
         $this->db->commit();

        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new ServiceException($e->getMessage(), $e->getCode(), $e);
        }

        return [
            'userId' => $user->id
        ];

    }

    /**
     * Uploaded avatar
     *
     * @param array $data
     * @return array
     */
    public function uploadedImage(array $data)
    {
        $imageArr = [];
        $supportTypes = [
         "image/png",
         "image/jpg",
         "image/gif",
        ];

        foreach ($data as $item) {
            $imageArr['name'] = $data['file']['name'];
            $imageArr['type'] = $data['file']['type'];
            $imageArr['size'] = $data['file']['size'];
        }
        if (!in_array($imageArr['type'],$supportTypes))
        {
            throw  new ServiceException(
                "This format is not supported",
                self::ERROR_FORMAT_IS_NOT_SUPPORT
            );
        }


        try {
            $this->db->begin();
            $avatar = new Avatars();
            $avatar->assign($imageArr);
            $result = $avatar->create();

            if (!$result) {
                throw new ServiceException(
                    'Unable to create user',
                    self::ERROR_UNABLE_CREATE_AVATAR
                );
            }

//
//            $ipAddress = $this->request->getClientAddress();
//            $userAgent = $this->request->getUserAgent();
//            $token = Helper::generateToken();

//            // Send email with confirmation link
//            $emailConfirmation = new EmailConfirmations();
//            $emailConfirmation->user_id = $user->id;
//            $emailConfirmation->token = $token;
//            if ($ipAddress) $emailConfirmation->ip_address = $ipAddress;
//            if ($userAgent) $emailConfirmation->user_agent = $userAgent;
//            $emailConfirmation->save();
//
            $this->db->commit();

        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new ServiceException($e->getMessage(), $e->getCode(), $e);
        }

        return [
            'avatarId' => $avatar->id
        ];

    }

}
