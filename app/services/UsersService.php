<?php

namespace App\Services;

use App\Exceptions\ServiceException;
use App\Models\Avatars;
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
     * @return null
     */
    public function create(array $data)
    {
        $file = $_FILES;
        $user = new Users();
        $user->assign($data);
        $isAvatar = Avatars::findFirst($user->id);

        $isCreated = $user->create();

        if ($isCreated !== true) {
            throw  new ServiceException(
                'Unable to create user',
                self::ERROR_NOT_EXISTS
            );
        }

        $file['file']['name'] = time();
        $supportTypes = [
            "image/png",
            "image/jpg",
            "image/jpeg",
            "image/gif",
        ];
        $imageType = '';

        switch ($file['file']['type']) {

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
                if (in_array($file['file']['type'], $supportTypes) === false) {
                    throw  new ServiceException(
                        "This format is not supported",
                        self::ERROR_FORMAT_IS_NOT_SUPPORT
                    );
                }
                break;
        };
        $uploadFolder = '/var/www/php/images/' . date("Y/m/d", time()) . '/' . $file['file']['name'] . "." . $imageType;

        if (!is_dir('/var/www/php/images/' . date("Y/m/d", time()))) {
            mkdir($uploadFolder, 0755, true);
            $uploaded = move_uploaded_file($file['file']['tmp_name'], $uploadFolder);
        }

        $uploaded = move_uploaded_file($file['file']['tmp_name'], $uploadFolder);

        if (!$isAvatar) {
            $avatar = new Avatars();
            $avatar->assign($file['file']);
            $avatar->user_id = $user->id;
            $avatar->path = $uploadFolder;
            $avatar->name = $file['file']['name'] . "." . $imageType;
            $result = $avatar->create();
            if (!$result) {
                throw new ServiceException(
                    'Unable to create avatar',
                    self::ERROR_UNABLE_TO_CREATE
                );
            }

        } else {
            // remove old avatar
            unlink('/var/www/php/images/' . $isAvatar->name);
            $imageName = $file['file']['name'] . "." . $imageType;
            $sql = "UPDATE avatars 
                SET name = :name, type = :type, size = :size, 
                WHERE user_id =:userId ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("name", $imageName, \PDO::PARAM_STR);
            $stmt->bindParam("type", $file['file']['type'], \PDO::PARAM_STR);
            $stmt->bindParam("size", $file['file']['size'], \PDO::PARAM_INT);
            $stmt->bindParam("userId", $userId, \PDO::PARAM_INT);
            $stmt->execute();
        }

        if ($uploaded === false) {
            throw  new ServiceException(
                "Unable to upload image",
                self::ERROR_UNABLE_TO_CREATE
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
