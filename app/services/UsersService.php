<?php

namespace App\Services;

use App\Exceptions\HttpExceptions\Http403Exception;
use App\Exceptions\ServiceException;
use App\Lib\Helper;
use App\Models\Avatars;
use App\Models\EmailConfirmations;
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
        try {
            // Start a transaction
            $this->db->begin();
            // Get uploaded file
            $file = $_FILES;
            // Connecting with Users models
            $user = new Users();
            // Insert data in database
            $user->assign($data);
            // Save data in db
            $isCreated = $user->create();

            if ($isCreated !== true) {
                throw new ServiceException(
                    'Unable to create user',
                    self::ERROR_NOT_EXISTS
                );
            }
            // Uploaded avatar
            $this->uploadAvatarFile($user->id, $file);

            $confirmToken = Helper::generateToken();

            $email = new EmailConfirmations();
            $email->user_id = $user->id;
            $email->token = $confirmToken;
            $email->user_agent = $this->request->getUserAgent();
            $email->ip_address = $this->request->getClientAddress();
            $email->create();

            // Commit the transaction
            $this->db->commit();

            $this->mailer->confirmEmail($user->email, $user->username, $confirmToken);

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new Http403Exception($e->getMessage(), self::ERROR_BAD_TOKEN, $e);
        }

        return null;

    }


    /**
     * details
     * @param int $userId
     * @return array
     */
    public function details(int $userId): array
    {
        $result = [];
        $jwt = $this->authService->getJwtToken();
        $loggedId = intval($this->authService->decodeJWT($jwt)->getPayload()['sub']);
        date_default_timezone_set('Europe/Sofia');
        $currentHour = date('H');

        if ($currentHour >= 0 && $currentHour < 12) {
            $result['greeting'] = ("Good Morning!");
        } else if ($currentHour == 12) {
            $result['greeting'] = ("Good Noon!");
        } else if ($currentHour >= 12 && $currentHour <= 17) {
            $result['greeting'] = ("Good Afternoon!");
        } else {
            $result['greeting'] = ("Good Evening!");
        }

        if ($userId !== $loggedId) {
            throw  new ServiceException(
                "User is not authorized",
                self::ERROR_USER_NOT_AUTHORIZED
            );
        }

        $sql = "SELECT 
                    u.username,
                    u.balance,
                    av.name,
                    av.path
                FROM users u
                INNER JOIN avatars av ON av.user_id = u.id
                WHERE u.id = :userId
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam('userId', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $result['userDetails'] = $stmt->fetchAll();

        if (!$result) {
            throw new  ServiceException(
                "Details is not found",
                self::ERROR_IS_NOT_FOUND
            );
        }

        return $result;
    }


    /**
     * Get from $_FILE  current type to string
     * @param string $type
     * @return string
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
                    throw new ServiceException(
                        "This format is not supported",
                        self::ERROR_FORMAT_IS_NOT_SUPPORT
                    );
                }
                break;
        };

        return $fileType;
    }

    /**
     * uploadAvatarFile
     * @param int | null $userId
     * @param array $file
     * @retrun null
     */

    private function uploadAvatarFile(int|null $userId = null, array $file): void
    {
        $file['file']['name'] = time();
        $types = $this->getTypeToString($file['file']['type']);
        (string)$path = $this->config->application->domain . '/images/' . date("Y/M/d", time()) . '/' . $file['file']['name'] . "." . $types;
        (string)$uploadFolder = '/var/www/php/public/images/' . date("Y/M/d", time()) . '/' . $file['file']['name'] . "." . $types;
        (string)$timeFolder = '/var/www/php/public/images/' . date("Y/M/d", time()) . '/';

        // If exist time folder  created
        if (!is_dir($timeFolder)) {
            mkdir($timeFolder, 0755, true);
        }

        // Call Avatar model
        $avatar = new Avatars();
        $avatar->assign($file['file']);
        $avatar->type = $types;
        $avatar->user_id = $userId;
        $avatar->path = $path;
        $avatar->name = $file['file']['name'] . "." . $types;
        $result = $avatar->create();
        // If not created
        if (!$result) {
            throw new ServiceException(
                'Unable to create avatar',
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        // Move from temp to uploaded folder
        $uploaded = move_uploaded_file($file['file']['tmp_name'], $uploadFolder);
        // Can`t upload image
        if ($uploaded === false) {
            throw new ServiceException(
                "Unable to upload image",
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

    }

}
