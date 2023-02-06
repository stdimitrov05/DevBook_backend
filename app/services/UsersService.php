<?php

namespace App\Services;

use App\Lib\Helper;
use App\Models\Avatars;
use App\Models\EmailConfirmations;
use App\Models\Users;
use App\Exceptions\ServiceException;
use  Intervention\Image\ImageManager;

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
        $data['file']['name'] = time();
        $supportTypes = [
            "image/png",
            "image/jpg",
            "image/jpeg",
            "image/gif",
        ];
        $imageType = '';
        switch ($data['file']['type']) {

            case  "image/png" :
                $imageType = "png";
                break;

            case  "image/jpg" :
                $imageType = "jpg";
                break;

            case  "image/jpeg" :
                $imageType = "jpeg";
                break;

            case  "image/gif" :
                $imageType = "gif";
                break;

            default :
                if (in_array($data['file']['type'], $supportTypes) === false) {
                    throw  new ServiceException(
                        "This format is not supported",
                        self::ERROR_FORMAT_IS_NOT_SUPPORT
                    );
                }
                break;
        };


        try {
            $this->db->begin();
            $avatar = new Avatars();
            $avatar->assign($data['file']);
            $result = $avatar->create();
            if (!$result) {
                throw new ServiceException(
                    'Unable to create user',
                    self::ERROR_UNABLE_CREATE_AVATAR
                );
            }

            $uploadFolder = '/var/www/php/files/' . $data['file']['name'] . "." . $imageType;
            $uploaded = move_uploaded_file($data['file']['tmp_name'], $uploadFolder);

            if ($uploaded === false ) {
                throw  new ServiceException(
                    "Unable to upload image",
                    self::ERROR_UNABLE_TO_UPLOAD_IMAGE
                );
            }

            $manager =  new ImageManager(['driver' => 'imagick']);
            $image = $manager->make($uploadFolder);
            $image->resize(320,240);

            // Remove origin image
            $isRemoveImage = unlink($uploadFolder);

            if ( $isRemoveImage === true) {
                $image->save($uploadFolder);
            }


            $this->db->commit();

        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new ServiceException($e->getMessage(), $e->getCode(), $e);
        }

        return [
            'avatarId' => $avatar->id,

        ];

    }

}
