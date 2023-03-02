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
        // Get uploaded file
        $file = $_FILES;
        // Connecting with Users models
        $user = new Users();
        // Insert data in database
        $user->assign($data);
        // Save data in db
        $isCreated = $user->create();

        if ($isCreated !== true) {
            throw  new ServiceException(
                'Unable to create user',
                self::ERROR_NOT_EXISTS
            );
        }
        // Generate name
        $file['file']['name'] = time();
        // Get type as string
        $types = $this->getTypeToString($file['file']['type']);
        // Def folders
        (string)$uploadFolder = '/var/www/php/public/images/' . date("Y/m/d", time()) . '/' . $file['file']['name'] . "." . $types;
        (string)$timeFolder = '/var/www/php/public/images/' . date("Y/m/d", time()) . '/';
        // If exist time folder  created
        if (!is_dir($timeFolder)) {
            mkdir($timeFolder, 0755, true);
        }
        // Move from temp to uploaded folder
        $uploaded = move_uploaded_file($file['file']['tmp_name'], $uploadFolder);
        // Can`t upload image
        if ($uploaded === false) {
            throw  new ServiceException(
                "Unable to upload image",
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        // Call Avatar model
        $avatar = new Avatars();
        $avatar->assign($file['file']);
        $avatar->type = $types;
        $avatar->user_id = $user->id;
        $avatar->path = $uploadFolder;
        $avatar->name = $file['file']['name'] . "." . $types;
        $result = $avatar->create();
        // If not created
        if (!$result) {
            throw new ServiceException(
                'Unable to create avatar',
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        // Resize image to 320x240
        $manager = new ImageManager(['driver' => 'imagick']);
        $image = $manager->make($uploadFolder);
        $image->resize(320, 240);

        // Remove origin image
        $isRemoveImage = unlink($uploadFolder);
        // If all gone save to uploaded folder
        if ($isRemoveImage === true) {
            $image->save($uploadFolder);
        }

        return null;

    }

    /**
     * Get from $_FILE  current type to string
     * @param string $type
     * @retrun  string
     */

    private function getTypeToString(string $type): string
    {
        (string)$fileType = '';

        // Supports Types as array
        $supportTypes = [
            "image/png",
            "image/jpg",
            "image/jpeg",
            "image/gif",
        ];

        // Check types
        switch ($type) {
            case  "image/png" :
                $fileType = "png";
                break;

            case  "image/jpg" :
                $fileType = "jpg";
                break;

            case  "image/jpeg" :
                $fileType = "jpeg";
                break;

            case  "image/gif" :
                $fileType = "gif";
                break;

            default :
                if (in_array($type, $supportTypes) === false) {
                    throw  new ServiceException(
                        "This format is not supported",
                        self::ERROR_FORMAT_IS_NOT_SUPPORT
                    );
                }
                break;
        };

        return $fileType;
    }

}
