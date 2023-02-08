<?php

namespace App\Services;

use App\Exceptions\ServiceException;
use App\Models\Avatars;
use Intervention\Image\ImageManager;

/**
 * Business-logic for users
 *
 * Class UsersService
 */
class AvatarsService extends AbstractService
{

    /**
     * Uploaded avatar
     *
     * @param array $data
     * @return array
     */
    public function upload(array $data)
    {
        $userId = $this->authService->getIdentity();
        $isAvatar = Avatars::findFirstByUser_id($userId);

        if (!$userId) {
            throw  new ServiceException(
                "User is not found",
                self::ERROR_USER_NOT_FOUND
            );
        }

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

        $uploadFolder = '/var/www/php/images/' . $data['file']['name'] . "." . $imageType;
        $uploaded = move_uploaded_file($data['file']['tmp_name'], $uploadFolder);


        if (!$isAvatar) {
            $avatar = new Avatars();
            $avatar->assign($data['file']);
            $avatar->user_id = $userId;
            $avatar->name = $data['file']['name'] . "." . $imageType;
            $result = $avatar->create();
            if (!$result) {
                throw new ServiceException(
                    'Unable to create avatar',
                    self::ERROR_UNABLE_CREATE_AVATAR
                );
            }

        } else {
            // remove old avatar
            unlink('/var/www/php/images/' . $isAvatar->name);
            $imageName = $data['file']['name'] . "." . $imageType;
            $sql = "UPDATE avatars 
                SET name = :name, type = :type, size = :size, updated_at=:createdAt   
                WHERE user_id =:userId ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("name", $imageName, \PDO::PARAM_STR);
            $stmt->bindParam("type", $data['file']['type'], \PDO::PARAM_STR);
            $stmt->bindParam("size", $data['file']['size'], \PDO::PARAM_INT);
            $stmt->bindParam("createdAt", time(), \PDO::PARAM_INT);
            $stmt->bindParam("userId", $userId, \PDO::PARAM_INT);
            $stmt->execute();
        }

        if ($uploaded === false) {
            throw  new ServiceException(
                "Unable to upload image",
                self::ERROR_UNABLE_TO_UPLOAD_IMAGE
            );
        }

        $manager = new ImageManager(['driver' => 'imagick']);
        $image = $manager->make($uploadFolder);
        $image->resize(320, 240);

        // Remove origin image
        $isRemoveImage = unlink($uploadFolder);

        if ($isRemoveImage === true) {
            $image->save($uploadFolder);
        }


        return null;

    }

}
