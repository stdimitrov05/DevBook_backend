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
     * @return null
     */
    public function create(array $data)
    {
        try {
            // Start a transaction
            $this->db->begin();
            // Connecting with Users models
            $user = new Users();
            // Insert data in database
            $user->assign($data);
            // Save data in db
            $isCreated = $user->create();

            if ($isCreated !== true) {
                throw new ServiceException(
                    'Unable to create user',
                    self::ERROR_UNABLE_TO_CREATE
                );
            }

            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'password' => $user->password,
                'balance' => $user->balance,
                'active' => $user->active,
                'created_at' => $user->created_at,
                'deleted_at' => $user->deleted_at,

            ];
            // Insert in elasticsearch
            $this->elastic->insertUserData($userData);

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

        return [
            "userId" => $user->id
        ];

    }

    /**
     * details
     * @param int $userId
     * @return array
     */
    public function details(int $userId): array
    {

        $result = [];
        $loggedId = $this->authService->userId();

        $user = Users::findFirstById($loggedId);

        $this->authService->checkUserFlags($user);

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

        // Get avatar
        $result['elastic'] = $this->elastic->getUserData($loggedId);

        return $result;
    }

    /**
     * Set billing details
     * @param array $data
     * @return null
     */
    public function billing( array $data)
    {
        // User is authorized
        $data['user_id']  = $this->authService->userId();


        $haveBilling = UserBillings::findFirst(
            [
                'conditions' => 'user_id = :userId:',
                'bind' => ['userId' =>  $data['user_id']]
            ]);

        if (!$haveBilling) {
            $billing = new UserBillings();
            $billing->user_id =  $data['user_id'];
            $billing->location_id = $data['location_id'];
            $billing->description = $data['description'];
            $created = $billing->create();

            if ($created !== true) {
                throw  new ServiceException(
                    "Unable to save billing",
                    self::ERROR_UNABLE_TO_CREATE
                );
            }
            // Insert to elastic
            $this->elastic->insertUserBilling($billing->id, $data);
        } else {
            throw  new ServiceException(
                "You can`t save billing again",
                self::ERROR_UNABLE_TO_CREATE
            );
        }


        return null;
    }

    /**
     * updateDetails
     * @param int $userId ,
     * @param array $data
     * @retrun  null
     * */
    public function updateDetails(int $userId, array $data)
    {
        $userId = $this->authService->userId();

        try {
            // Start a transaction
            $this->db->begin();

            // Get user data
            $user = Users::findFirstById($userId);

            if ($user->username !== $data['username']) {
                $user->username = $data['username'];

                // Update username in elastic
                $this->elastic->updateUsername($userId, $data['username']);
                $user->update();

            }

            // Get user billing data
            $userBilling = UserBillings::findFirst([
                'conditions' => 'user_id = :userId:',
                'bind' => ['userId' => $userId]
            ]);


            if ($userBilling->location_id !== $data['location_id'] && $userBilling->description !== $data['description']
                || $userBilling->location_id !== $data['location_id'] || $userBilling->description !== $data['description']
            ) {
                $userBilling->location_id = $data['location_id'];
                $userBilling->description = $data['description'];

                $updateData  = [
                    'location_id'=>$data['location_id'],
                    'description'=>$data['description'],
                ];
                // Update in elastic
                $this->elastic->updateBilling($userBilling->id,$updateData);

                $userBilling->update();

            }


            $this->db->commit();


        } catch (\Exception $exception) {
            $this->db->rollback();
            throw new Http403Exception($exception->getMessage(), self::ERROR_BAD_TOKEN, $exception);

        }
        return null;

    }


    /**
     * delete
     * @param int $userId
     * @retrun  null
     * */

    public function delete(int $userId)
    {
        $loggedId = $this->authService->userId();

        if ($userId !== $loggedId) {
            throw  new ServiceException(
                "User is not authorized",
                self::ERROR_USER_NOT_AUTHORIZED
            );
        }

        // Update data in database
        $user = Users::findFirst([
            'conditions' => 'id = :id:',
            'bind' => ['id' => $userId]
        ]);

        if (!$user) {
            throw  new ServiceException(
                "User is not found",
                self::ERROR_NOT_FOUND
            );
        }

        // Check user flags
        $this->authService->checkUserFlags($user);

        $user->active = 0;
        $user->deleted_at = time();
        $deleted = $user->update();

        if ($deleted !== true) {
            throw new ServiceException(
                "Unable to update deleted_at",
                self::ERROR_UNABLE_TO_UPDATE
            );
        }

        // Clear in elastic
        $isDeleted = $this->elastic->deleteUserById($userId);

        if ($isDeleted !== true) {
            throw  new ServiceException(
                "Unable to delete account",
                self::ERROR_UNABLE_TO_DELETE
            );
        }

        // Clear in redis
        $this->redisService->clearJwtByUserId($userId);

        return null;
    }



    /**
     * uploadAvatar
     * @param array $file
     * */
    public function uploadAvatar( array $file)
    {
        $userId = $this->authService->userId();

        $avatar = Avatars::findFirst([
            'conditions' => 'user_id = ?1 ',
            'bind' => [1 => $userId],
        ]);

        if (!$avatar) {
            if ($file) {
                $this->uploadAvatarFile($userId, $file);
            }
        }

        return null;

    }

    /**
     * uploadAvatarFile
     * @param int | null $userId
     * @param array $file
     * @retrun null
     */
    private function uploadAvatarFile(int|null $userId = null, array $file): void
    {
        $fileObj = [];
        foreach ($file as $data) {
            $fileObj ["name"] = time();
            $fileObj ["extension"] = $data->getExtension();
            $fileObj ["size"] = $data->getSize();
            $fileObj ["tmp"] = $data->getTempName();
            $fileObj ["type"] = $data->getType();
            $fileObj ["key"] = $data->getKey();
        }
        (string)$path = '/images/' . date("Y/M/d", time()) . '/' . $fileObj ["name"] . "." . $fileObj['extension'];
        (string)$uploadFolder = '/var/www/php/public/images/' . date("Y/M/d", time()) . '/' . $fileObj ["name"] . "." . $fileObj['extension'];
        (string)$timeFolder = '/var/www/php/public/images/' . date("Y/M/d", time()) . '/';
        // If exist time folder  created
        if (!is_dir($timeFolder)) {
            mkdir($timeFolder, 0755, true);
        }

        // Call Avatar model
        $avatar = new Avatars();
        $avatar->assign($fileObj);
        $avatar->type = $fileObj['type'];
        $avatar->extension = $fileObj['extension'];
        $avatar->user_id = $userId;
        $avatar->path = $path;
        $avatar->name = $fileObj["name"];
        $result = $avatar->create();
        // If not created
        if (!$result) {
            throw new ServiceException(
                'Unable to create avatar',
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        $avatar = [
            'id' => $avatar->id,
            'user_id' => $avatar->user_id,
            'name' => $avatar->name,
            'type' => $avatar->type,
            'size' => $avatar->size,
            'path' => $avatar->path,
        ];

        // Insert avatar in elastic
        $this->elastic->insertAvatarData($avatar);
        // Move from temp to uploaded folder
        $uploaded = move_uploaded_file($fileObj['tmp'], $uploadFolder);
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
